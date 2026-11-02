<?php

namespace App\Http\Controllers\Lpk;

use App\Http\Controllers\Controller;
use App\Models\QuestionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BankQuestionImportController extends Controller
{
    /**
     * Column layout of the import sheet: question, options A–E, correct letter.
     */
    private const HEADERS = ['Pertanyaan', 'Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D', 'Pilihan E', 'Jawaban (A-E)'];

    private const LETTERS = ['A', 'B', 'C', 'D', 'E'];

    private const MAX_ROWS = 1000;

    /**
     * Download an empty Excel template with two example rows.
     */
    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Soal');
        $sheet->fromArray([
            self::HEADERS,
            ['Jika semua A adalah B dan semua B adalah C, maka...', 'Semua A adalah C', 'Semua C adalah A', 'Tidak ada A yang C', 'Sebagian C bukan B', '', 'A'],
            ['Ibu kota provinsi Jawa Barat adalah...', 'Semarang', 'Bandung', 'Surabaya', 'Serang', '', 'B'],
        ]);
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(60);
        foreach (['B', 'C', 'D', 'E', 'F'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(25);
        }
        $sheet->getColumnDimension('G')->setWidth(15);

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            'template-bank-soal.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * Import questions from an Excel/CSV file into a category. The whole
     * file is validated first; nothing is saved if any row is invalid.
     * Questions already in the category (same text) are skipped.
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

        DB::transaction(function () use ($questions, $category) {
            foreach ($questions as [$text, $options, $correctIndex]) {
                $category->questions()->create(['question' => $text])->syncOptions($options, $correctIndex);
            }
        });

        $message = count($questions).' soal berhasil diimpor ke "'.$category->name.'".';
        if ($duplicates > 0) {
            $message .= " {$duplicates} soal dilewati karena sudah ada.";
        }

        return back()->with('status', $message);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{0: list<array{0: string, 1: array<int, string>, 2: int}>, 1: list<string>, 2: int}
     */
    private function parseRows(array $rows, QuestionCategory $category): array
    {
        $existing = $category->questions()->pluck('question')
            ->mapWithKeys(fn (string $text) => [$this->normalize($text) => true])
            ->all();

        $questions = [];
        $errors = [];
        $duplicates = 0;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            $cells = array_map(fn ($value) => trim((string) $value), array_pad(array_slice($row, 0, 7), 7, ''));

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

            $problem = match (true) {
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
            if (isset($existing[$key])) {
                $duplicates++;

                continue;
            }

            $existing[$key] = true;
            $questions[] = [$text, $options, $correctIndex];
        }

        return [$questions, $errors, $duplicates];
    }

    private function normalize(string $text): string
    {
        return Str::of($text)->lower()->squish()->toString();
    }
}
