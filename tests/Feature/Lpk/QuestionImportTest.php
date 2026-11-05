<?php

namespace Tests\Feature\Lpk;

use App\Models\BankQuestion;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class QuestionImportTest extends TestCase
{
    use RefreshDatabase;

    private User $lpk;

    private QuestionCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lpk = User::factory()->lpk()->create();
        $this->category = $this->lpk->organization->questionCategories()->where('name', 'Psikotes')->firstOrFail();
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function xlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'soal').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'soal.xlsx', null, null, true);
    }

    /**
     * @return array<int, string>
     */
    private function header(): array
    {
        return ['Pertanyaan', 'Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D', 'Pilihan E', 'Jawaban (A-E)'];
    }

    public function test_template_can_be_downloaded(): void
    {
        $response = $this->actingAs($this->lpk)->get(route('lpk.bank.template'));

        $response->assertOk();
        $this->assertStringContainsString('template-bank-soal.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_questions_are_imported_from_excel(): void
    {
        $file = $this->xlsx([
            $this->header(),
            ['Sinonim kata "cepat"', 'Lambat', 'Lekas', 'Diam', '', '', 'B'],
            ['5 x 6 = ?', '30', '11', '56', '25', '36', 'a'],
        ]);

        $this->actingAs($this->lpk)
            ->post(route('lpk.categories.import', $this->category), ['file' => $file])
            ->assertSessionHas('status');

        $this->assertSame(2, $this->category->questions()->count());
        $question = $this->category->questions()->where('question', '5 x 6 = ?')->with('options')->firstOrFail();
        $this->assertCount(5, $question->options);
        $this->assertSame('30', $question->options->firstWhere('is_correct', true)->text);
    }

    public function test_csv_files_are_accepted(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'soal').'.csv';
        file_put_contents($path, "Pertanyaan,Pilihan A,Pilihan B,Pilihan C,Pilihan D,Pilihan E,Jawaban\n\"Warna langit cerah?\",Merah,Biru,,,,B\n");

        $this->actingAs($this->lpk)->post(route('lpk.categories.import', $this->category), [
            'file' => new UploadedFile($path, 'soal.csv', 'text/csv', null, true),
        ])->assertSessionHas('status');

        $this->assertSame('Biru', $this->category->questions()->firstOrFail()->options()->where('is_correct', true)->value('text'));
    }

    public function test_file_with_an_invalid_row_imports_nothing(): void
    {
        $file = $this->xlsx([
            $this->header(),
            ['Soal benar?', 'Ya', 'Tidak', '', '', '', 'A'],
            ['Soal tanpa jawaban', 'Ya', 'Tidak', '', '', '', ''],
            ['Jawaban ke pilihan kosong', 'Ya', 'Tidak', '', '', '', 'D'],
        ]);

        $response = $this->actingAs($this->lpk)->post(route('lpk.categories.import', $this->category), ['file' => $file]);

        $response->assertSessionHasErrorsIn('import', 'file');
        $errors = session('errors')->getBag('import')->get('file');
        $this->assertStringContainsString('Baris 3', $errors[0]);
        $this->assertStringContainsString('Baris 4', $errors[1]);
        $this->assertSame(0, $this->category->questions()->count());
    }

    public function test_duplicate_questions_are_skipped(): void
    {
        BankQuestion::factory()->for($this->category, 'category')->withOptions()->create(['question' => 'Soal  yang sudah ada?']);

        $file = $this->xlsx([
            $this->header(),
            ['soal yang sudah ada?', 'A', 'B', '', '', '', 'A'],
            ['Soal baru?', 'A', 'B', '', '', '', 'A'],
            ['Soal baru?', 'A', 'B', '', '', '', 'A'],
        ]);

        $this->actingAs($this->lpk)->post(route('lpk.categories.import', $this->category), ['file' => $file]);

        $this->assertSame(2, $this->category->questions()->count());
        $this->assertStringContainsString('2 soal dilewati', session('status'));
    }

    public function test_non_spreadsheet_files_are_rejected(): void
    {
        $this->actingAs($this->lpk)
            ->post(route('lpk.categories.import', $this->category), ['file' => UploadedFile::fake()->create('soal.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrorsIn('import', 'file');
    }

    public function test_exported_file_can_be_corrected_and_imported_back(): void
    {
        $question = BankQuestion::factory()->for($this->category, 'category')->create(['question' => 'Ibu kota Jawa Tengah?']);
        $question->syncOptions(['Semarang', 'Solo', 'Kudus'], 1);
        $untouched = BankQuestion::factory()->for($this->category, 'category')->create(['question' => 'Satu tambah satu?']);
        $untouched->syncOptions(['1', '2'], 1);

        $response = $this->actingAs($this->lpk)->get(route('lpk.categories.export', $this->category));
        $response->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
        file_put_contents($path, $response->streamedContent());
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, false);

        $this->assertSame(['Ibu kota Jawa Tengah?', 'Semarang', 'Solo', 'Kudus', '', '', 'B', $question->id], array_map(
            fn ($value) => $value ?? '', $rows[1],
        ));

        // Fix the answer key of the first question and add a new one.
        $rows[1][6] = 'A';
        $rows[] = ['Soal baru?', 'Ya', 'Tidak', '', '', '', 'A', ''];

        $this->post(route('lpk.categories.import', $this->category), ['file' => $this->xlsx($rows)])
            ->assertSessionHas('status', '1 soal baru diimpor, 1 soal diperbarui di "Psikotes".');

        $this->assertSame(3, $this->category->questions()->count());
        $this->assertSame('Semarang', $question->options()->where('is_correct', true)->value('text'));
    }

    public function test_import_rejects_ids_from_outside_the_category(): void
    {
        $foreign = BankQuestion::factory()->withOptions()->create();

        $this->actingAs($this->lpk)
            ->post(route('lpk.categories.import', $this->category), ['file' => $this->xlsx([['Soal?', 'A', 'B', '', '', '', 'A', $foreign->id]])])
            ->assertSessionHasErrorsIn('import', 'file');

        $this->assertSame(0, $this->category->questions()->count());
    }

    public function test_lpk_can_not_export_another_lpks_category(): void
    {
        $this->actingAs($this->lpk)
            ->get(route('lpk.categories.export', QuestionCategory::factory()->create()))
            ->assertForbidden();
    }

    public function test_lpk_can_not_import_into_another_lpks_category(): void
    {
        $otherCategory = QuestionCategory::factory()->create();

        $this->actingAs($this->lpk)
            ->post(route('lpk.categories.import', $otherCategory), ['file' => $this->xlsx([['Soal?', 'A', 'B', '', '', '', 'A']])])
            ->assertForbidden();

        $this->assertSame(0, $otherCategory->questions()->count());
    }
}
