@push('styles')
<style>
    .gantt_resource_marker_ok {
        background-color: #22c55e;
        /* Hijau normal */
        color: white;
    }

    .gantt_resource_marker_overtime {
        background-color: #ef4444;
        /* Merah overload */
        color: white;
    }

    .today-header {
        background-color: #3b82f6;
        /* Biru untuk header hari ini */
        color: white;
        font-weight: bold;
    }

    .weekend-header {
        background-color: #facc15;
        /* Kuning untuk weekend */
        font-weight: bold;
    }

    .weekend-cell {
        background-color: #fef9c3;
        /* Kuning muda untuk sel weekend */
    }
</style>
@endpush

<div class="flex flex-col gap-4">
    <div class="flex items-center gap-2 mb-4">

        <!-- Previous -->
        <button wire:click="previousRange"
            class="px-3 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 border border-gray-300">
            ←
        </button>

        <!-- Weekly -->
        <button wire:click="setRange('weekly')"
            class="px-4 py-2 rounded-lg font-semibold transition-all duration-150
        {{ $range_type === 'weekly' 
            ? 'bg-white-200 text-white-800 hover:bg-white-300 border border-white-300' 
            : 'bg-gray-200 text-gray-800 hover:bg-gray-300 border border-gray-300' }}">
            Weekly
        </button>

        <!-- Monthly -->
        <button wire:click="setRange('monthly')"
            class="px-4 py-2 rounded-lg font-semibold transition-all duration-150
        {{ $range_type === 'monthly' 
            ? 'bg-white-200 text-white-800 hover:bg-white-300 border border-white-300' 
            : 'bg-gray-200 text-gray-800 hover:bg-gray-300 border border-gray-300' }}">
            Monthly
        </button>

       <!-- Next -->
        <button wire:click="nextRange"
            class="px-3 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 border border-gray-300">
            →
        </button>

    </div>
    <!-- Filter tanggal -->
    <div class="w-full mb-4">
        {{ $this->form }}
    </div>

    <!-- Legend warna -->
    <div class="flex gap-4 mb-2 text-sm">
        <div class="flex items-center gap-1">
            <div class="w-4 h-4 gantt_resource_marker_ok border border-gray-400"></div> Normal (≤ 8 jam)
        </div>
        <div class="flex items-center gap-1">
            <div class="w-4 h-4 gantt_resource_marker_overtime border border-gray-400"></div> Overload (> 8 jam)
        </div>
        <div class="flex items-center gap-1">
            <div class="w-4 h-4 today-header border border-gray-400"></div> Today
        </div>
        <div class="flex items-center gap-1">
            <div class="w-4 h-4 weekend-header border border-gray-400"></div> Weekend
        </div>
    </div>

    <!-- Tabel workload -->
    <div class="overflow-x-auto">
        <table class="table-auto border-collapse border border-gray-300 w-full text-sm">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-2 py-1 text-left">Name</th>
                    <th class="border px-2 py-1 text-center">Total</th>
                    @foreach ($resourcesData['dates'] as $date)
                    @php
                    $carbonDate = \Carbon\Carbon::parse($date);
                    $isToday = $date === now()->format('Y-m-d');
                    $isWeekend = $carbonDate->isWeekend();
                    @endphp
                    <th class="border px-2 py-1 text-center 
                            {{ $isToday ? 'today-header' : '' }} 
                            {{ $isWeekend ? 'weekend-header' : '' }}">
                        {{ $carbonDate->format('d M') }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($resourcesData['rows'] as $row)
                <tr>
                    <td class="border px-2 py-1">{{ $row['name'] }}</td>
                    <td class="border px-2 py-1 text-center font-semibold">{{ $row['workload'] }}</td>
                    @foreach ($resourcesData['dates'] as $date)
                    @php
                    $jam = $row['days'][$date];
                    $warna = $jam > 8 ? 'gantt_resource_marker_overtime' : ($jam > 0 ? 'gantt_resource_marker_ok' : '');
                    $carbonDate = \Carbon\Carbon::parse($date);
                    $isWeekend = $carbonDate->isWeekend();
                    @endphp
                    <td class="border px-2 py-1 text-center {{ $warna }} {{ $isWeekend ? 'weekend-cell' : '' }}">
                        {{ $jam > 0 ? $jam : '' }}
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>