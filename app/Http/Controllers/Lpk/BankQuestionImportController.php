<?php

namespace App\Http\Controllers\Lpk;

use App\Http\Controllers\Controller;
use App\Models\BankQuestion;
use App\Models\QuestionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BankQuestionImportController extends Controller
{
    /**
     * Column layout of the sheet: question, options A–E, correct letter and
     * the question ID (filled by the export, empty for new questions).
     */
    private const HEADERS = ['Pertanyaan', 'Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D', 'Pilihan E', 'Jawaban (A-E)', 'ID (jangan diubah)'];

    private const LETTERS = ['A', 'B', 'C', 'D', 'E'];

    private const MAX_ROWS = 1000;

    /**
     * Download an empty Excel template with two example rows.
     */
    public function template(): StreamedResponse
    {
        return $this->download([
            ['Jika semua A adalah B dan semua B adalah C, maka...', 'Semua A adalah C', 'Semua C adalah A', 'Tidak ada A yang C', 'Sebagian C bukan B', '', 'A', ''],
            ['Ibu kota provinsi Jawa Barat adalah...', 'Semarang', 'Bandung', 'Surabaya', 'Serang', '', 'B', ''],
        ], 'template-bank-soal.xlsx');
    }

    /**
     * Download every question of a category in the import layout, so it can
     * be reviewed or corrected in Excel and imported back. Rows keep their ID,
     * which makes the import update them instead of adding duplicates.
     */
    public function export(QuestionCategory $category): StreamedResponse
    {
        Gate::authorize('manage', $category);

        $rows = $category->questions()->with('options')->orderBy('id')->get()
            ->map(function (BankQuestion $question) {
                $options = $question->options->pluck('text')->pad(5, '')->take(5)->all();
                $correctIndex = $question->options->search(fn ($option) => $option->is_correct);

                return [$question->question, ...$options, $correctIndex === false ? '' : self::LETTERS[$correctIndex], $question->id];
            })
            ->all();

        return $this->download($rows, 'bank-soal-'.Str::slug($category->name).'-'.now()->format('Ymd-His').'.xlsx');
    }

    /**
     * Import questions from an Excel/CSV file into a category. The whole
     * file is validated first; nothing is saved if any row is invalid.
     * Rows with an ID update that question; new questions identical to an
     * existing one (same text) are skipped.
     */
    public function store(Request $request, QuestionCategory $category): RedirectResponse
    {
        Gate::authorize('manage', $category);

        $request->validateWithBag('import', [
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:5120'],
        ], [], ['file' => 'file Excel']);

        $file = $request->file('file');

        try {
            $reader = IOFactory::createReader(match (strtolower($file->getClientOriginalExtension())) {
                'xls' => 'Xls',
                'csv' => 'Csv',
                default => 'Xlsx',
            });
            $reader->setReadDataOnly(true);
            $rows = $reader->load($file->getRealPath())->getActiveSheet()->toArray(null, true, false, false);
        } catch (Throwable) {
            return back()->withErrors(['file' => 'File tidak dapat dibaca. Pastikan formatnya .xlsx, .xls, atau .csv sesuai template.'], 'import');
        }

        [$questions, $errors, $duplicates] = $this->parseRows($rows, $category);

        if ($errors !== []) {
            return back()->withErrors(['file' => array_slice($errors, 0, 15)], 'import');
        }

        if ($questions === []) {
            return back()->withErrors(['file' => $duplicates > 0
                ? "Tidak ada soal baru. {$duplicates} soal sudah ada di kategori ini."
                : 'File tidak berisi soal.'], 'import');
        }

        $existing = $category->questions()->with('options')->get()->keyBy('id');
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($questions, $category, $existing, &$created, &$updated) {
            foreach ($questions as ['id' => $id, 'text' => $text, 'options' => $options, 'correct' => $correctIndex]) {
                if ($id === null) {
                    $category->questions()->create(['question' => $text])->syncOptions($options, $correctIndex);
                    $created++;

                    continue;
                }

                $question = $existing[$id];
                if ($this->isUnchanged($question, $text, $options, $correctIndex)) {
                    continue;
                }

                $question->update(['question' => $text]);
                $question->syncOptions($options, $correctIndex);
                $updated++;
            }
        });

        if ($created === 0 && $updated === 0) {
            return back()->with('status', 'Tidak ada perubahan: semua soal di file sama dengan bank soal.');
        }

        $parts = array_filter([
            $created > 0 ? "{$created} soal baru diimpor" : null,
            $updated > 0 ? "{$updated} soal diperbarui" : null,
        ]);
        $message = ucfirst(implode(', ', $parts)).' di "'.$category->name.'".';
        if ($duplicates > 0) {
            $message .= " {$duplicates} soal dilewati karena sudah ada.";
        }

        return back()->with('status', $message);
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     */
    private function download(array $rows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Soal');
        $sheet->fromArray([self::HEADERS, ...$rows]);
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('H1:H'.(count($rows) + 1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        $sheet->getStyle('A:F')->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension('A')->setWidth(60);
        foreach (['B', 'C', 'D', 'E', 'F'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(25);
        }
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->freezePane('A2');

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{0: list<array{id: int|null, text: string, options: array<int, string>, correct: int}>, 1: list<string>, 2: int}
     */
    private function parseRows(array $rows, QuestionCategory $category): array
    {
        $storedIds = $category->questions()->pluck('id')->flip()->all();

        /** @var array<string, int> $textOwners normalized text => question id (0 = new row in this file) */
        $textOwners = $category->questions()->pluck('question', 'id')
            ->mapWithKeys(fn (string $text, int $id) => [$this->normalize($text) => $id])
            ->all();

        $questions = [];
        $errors = [];
        $duplicates = 0;
        $seenIds = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            $cells = array_map(fn ($value) => trim((string) $value), array_pad(array_slice($row, 0, 8), 8, ''));

            if (implode('', $cells) === '') {
                continue;
            }

            if ($index === 0 && Str::contains(Str::lower($cells[0]), 'pertanyaan')) {
                continue;
            }

            if (count($questions) + count($errors) >= self::MAX_ROWS) {
                $errors[] = 'Maksimal '.self::MAX_ROWS.' soal per file.';
                break;
            }

            [$text, $options, $answer] = [$cells[0], array_slice($cells, 1, 5), Str::upper(Str::substr($cells[6], 0, 1))];
            $correctIndex = array_search($answer, self::LETTERS, true);
            $id = match (true) {
                $cells[7] === '' => null,
                ctype_digit($cells[7]) => (int) $cells[7],
                default => -1,
            };

            $problem = match (true) {
                $id !== null && ! isset($storedIds[$id]) => 'ID soal tidak ditemukan di kategori ini (kosongkan kolom ID untuk soal baru)',
                $id !== null && isset($seenIds[$id]) => "ID {$id} muncul lebih dari sekali",
                $text === '' => 'pertanyaan kosong',
                mb_strlen($text) > 2000 => 'pertanyaan terlalu panjang (maks 2000 karakter)',
                count(array_filter($options, fn ($option) => $option !== '')) < 2 => 'minimal 2 pilihan jawaban',
                collect($options)->contains(fn ($option) => mb_strlen($option) > 255) => 'pilihan jawaban maks 255 karakter',
                $correctIndex === false => 'kolom Jawaban harus berisi huruf A–E',
                $options[$correctIndex] === '' => "jawaban {$answer} menunjuk pilihan yang kosong",
                default => null,
            };

            if ($problem) {
                $errors[] = "Baris {$rowNumber}: {$problem}.";

                continue;
            }

            $key = $this->normalize($text);
            $owner = $textOwners[$key] ?? null;

            if ($id === null && $owner !== null) {
                $duplicates++;

                continue;
            }

            if ($id !== null && $owner !== null && $owner !== $id) {
                $errors[] = "Baris {$rowNumber}: pertanyaan sama persis dengan soal lain di kategori ini.";

                continue;
            }

            if ($id !== null) {
                $seenIds[$id] = true;
            }

            $textOwners[$key] = $id ?? 0;
            $questions[] = ['id' => $id, 'text' => $text, 'options' => $options, 'correct' => $correctIndex];
        }

        return [$questions, $errors, $duplicates];
    }

    /**
     * Determine if an imported row matches the stored question exactly.
     *
     * @param  array<int, string>  $options
     */
    private function isUnchanged(BankQuestion $question, string $text, array $options, int $correctIndex): bool
    {
        $filled = array_values(array_filter($options, fn (string $option) => $option !== ''));

        return $question->question === $text
            && $question->options->pluck('text')->all() === $filled
            && $question->options->firstWhere('is_correct', true)?->text === $options[$correctIndex];
    }

    private function normalize(string $text): string
    {
        return Str::of($text)->lower()->squish()->toString();
    }
}
