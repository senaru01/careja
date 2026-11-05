{{--
    Editable position table (positions[i][...]): one row per position with its
    quota, minimum education, age range and work experience.
    $positions: rows to start with (models or arrays); old input wins.
--}}
@props(['positions' => []])

@php
    $blank = ['position' => '', 'quota' => '', 'gender' => '', 'min_education' => '', 'min_age' => '', 'max_age' => '', 'min_experience_years' => 0, 'notes' => ''];
    $rows = collect(old('positions', collect($positions)->map(fn ($row) => [
        'position' => data_get($row, 'position'),
        'quota' => data_get($row, 'quota'),
        'gender' => data_get($row, 'gender') instanceof \BackedEnum ? data_get($row, 'gender')->value : data_get($row, 'gender'),
        'min_education' => data_get($row, 'min_education') instanceof \BackedEnum ? data_get($row, 'min_education')->value : data_get($row, 'min_education'),
        'min_age' => data_get($row, 'min_age'),
        'max_age' => data_get($row, 'max_age'),
        'min_experience_years' => data_get($row, 'min_experience_years', 0),
        'notes' => data_get($row, 'notes'),
    ])->all()))->map(fn ($row) => array_merge($blank, array_map(fn ($value) => $value ?? '', (array) $row)))->values();

    if ($rows->isEmpty()) {
        $rows->push($blank);
    }

    $educations = collect(\App\Enums\EducationLevel::cases())
        ->reject(fn ($level) => $level === \App\Enums\EducationLevel::Smk)
        ->mapWithKeys(fn ($level) => [$level->value => $level->minimumLabel()]);
    $cell = 'w-full rounded-lg border-slate-200 bg-white px-2 py-1.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500';
@endphp

<div x-data="{ rows: @js($rows), blank: @js($blank), total() { return this.rows.reduce((sum, row) => sum + (parseInt(row.quota) || 0), 0) } }" {{ $attributes }}>
    <div class="overflow-x-auto rounded-2xl ring-1 ring-slate-200">
        <table class="min-w-[66rem] w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-2 py-2 w-8">#</th>
                    <th class="px-2 py-2">Posisi</th>
                    <th class="px-2 py-2 w-20">Jumlah</th>
                    <th class="px-2 py-2 w-36">Jenis kelamin</th>
                    <th class="px-2 py-2 w-40">Pendidikan min.</th>
                    <th class="px-2 py-2 w-40">Umur (th)</th>
                    <th class="px-2 py-2 w-56">Pengalaman</th>
                    <th class="px-2 py-2">Keterangan</th>
                    <th class="px-2 py-2 w-8"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <template x-for="(row, index) in rows" :key="index">
                    <tr class="align-top">
                        <td class="px-2 py-2 pt-3.5 text-slate-400" x-text="index + 1"></td>
                        <td class="px-2 py-2">
                            <input type="text" :name="`positions[${index}][position]`" x-model="row.position" class="{{ $cell }}" placeholder="mis. Operator Produksi" maxlength="255" required>
                        </td>
                        <td class="px-2 py-2">
                            <input type="number" min="1" max="1000" :name="`positions[${index}][quota]`" x-model="row.quota" class="{{ $cell }}" required>
                        </td>
                        <td class="px-2 py-2">
                            <select :name="`positions[${index}][gender]`" x-model="row.gender" class="{{ $cell }} pe-8">
                                <option value="">Semua</option>
                                @foreach (\App\Enums\Gender::cases() as $gender)
                                    <option value="{{ $gender->value }}">{{ $gender->label() }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-2 py-2">
                            <select :name="`positions[${index}][min_education]`" x-model="row.min_education" class="{{ $cell }} pe-8">
                                <option value="">Semua</option>
                                @foreach ($educations as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-2 py-2">
                            <div class="flex items-center gap-1">
                                <input type="number" min="15" max="70" :name="`positions[${index}][min_age]`" x-model="row.min_age" class="{{ $cell }}" placeholder="min">
                                <span class="text-slate-400">–</span>
                                <input type="number" min="15" max="70" :name="`positions[${index}][max_age]`" x-model="row.max_age" class="{{ $cell }}" placeholder="maks">
                            </div>
                        </td>
                        <td class="px-2 py-2">
                            <select :name="`positions[${index}][min_experience_years]`" x-model="row.min_experience_years" class="{{ $cell }} pe-8">
                                <option value="0">Fresh graduate boleh</option>
                                @foreach ([1, 2, 3, 4, 5] as $years)
                                    <option value="{{ $years }}">Pengalaman min. {{ $years }} th</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-2 py-2">
                            <input type="text" :name="`positions[${index}][notes]`" x-model="row.notes" class="{{ $cell }}" placeholder="opsional, mis. shift, bisa las SMAW" maxlength="255">
                        </td>
                        <td class="px-2 py-2 pt-3">
                            <button type="button" x-show="rows.length > 1" @click="rows.splice(index, 1)" class="text-red-500 hover:text-red-700" title="Hapus baris">&times;</button>
                        </td>
                    </tr>
                </template>
            </tbody>
            <tfoot class="bg-slate-50 text-sm">
                <tr>
                    <td colspan="2" class="px-2 py-2">
                        <button type="button" @click="rows.push({ ...blank })" class="font-semibold text-blue-700 hover:underline">+ Tambah posisi</button>
                    </td>
                    <td class="px-2 py-2 font-semibold text-slate-900" x-text="total() + ' org'"></td>
                    <td colspan="6" class="px-2 py-2 text-xs text-slate-500">Total kebutuhan semua posisi.</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($errors->has('positions') || $errors->has('positions.*'))
        <ul class="mt-2 space-y-0.5 text-sm text-red-600">
            @foreach (collect($errors->get('positions'))->merge(collect($errors->get('positions.*'))->flatten())->unique() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif
</div>
