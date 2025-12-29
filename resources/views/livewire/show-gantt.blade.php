@push('styles')
<link defer href="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css" rel="stylesheet">
<style>
    .weekend-header {
        background-color: #facc15;
        font-weight: bold;
    }

    .weekend-cell {
        background-color: #fef9c3;
    }

    :root {
        --dhx-gantt-font-size: 13px;
    }

    .overdue {
        background-color: #fee2e2 !important;
    }

    .today {
        background-color: blue;
    }
</style>
@endpush

<div class="flex flex-col gap-1">
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


        <div class="flex items-center gap-4 ml-6">
             <span class="font-semibold text-gray-700">Display tasks with priority:</span>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="priorityFilter" value="urgent" class="rounded" checked>
                Urgent
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="priorityFilter" value="high" class="rounded" checked>
                High
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="priorityFilter" value="medium" class="rounded" checked>
                Medium
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="priorityFilter" value="low" class="rounded" checked>
                Low
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="priorityFilter" value="not priority" class="rounded" checked>
                Not Priority
            </label>
        </div>
    </div>


    <div class="w-full mb-4">{{ $this->form }}</div>
    <x-filament-actions::modals />
    <div class="w-full" wire:ignore>
        <div id="gantt_here" style="width:100%; height:700px;"></div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js"></script>
<script>
    let ganttPageInitialized = false;
    let ganttData = @json($ganttData ?? ['data' => [], 'links' => []]);

    function initGanttConfig(startDate, endDate) {
        gantt.plugins({
            marker: true,
            quick_info: true
        });
        gantt.config = {
            ...gantt.config,
            date_format: "%Y-%m-%d",
            readonly: true,
            grid_width: 350,
            row_height: 40,
            task_height: 32,
            bar_height: 24,
            work_time: true,
            start_date: new Date(startDate),
            end_date: new Date(endDate),
            columns: [{
                    name: "text",
                    label: "Task name",
                    width: 200,
                    tree: true
                },
                {
                    name: "start_date",
                    label: "Start Date",
                    width: 120,
                    align: "center"
                },
                {
                    name: "duration",
                    label: "Duration",
                    width: 60,
                    align: "center"
                },
                {
                    name: "priority",
                    label: "Priority",
                    width: 90,
                    align: "center"
                },
            ]
        };
        gantt.setWorkTime({
            day: 0,
            hours: false
        }); // Minggu
        gantt.setWorkTime({
            day: 6,
            hours: false
        }); // Sabtu

        gantt.templates.task_color = (s, e, t) => t.color || null;
        gantt.templates.task_class = (s, e, t) => t.is_overdue ? "overdue" : "";
        gantt.templates.tooltip_text = (s, e, t) => `
            <b>Task:</b> ${t.text}<br/>
            <b>Duration:</b> ${t.duration} day(s)<br/>
            <b>Estimasi Jam:</b> ${t.estimasi_jam}<br/>
            <b>Start:</b> ${gantt.templates.tooltip_date_format(s)}<br/>
            <b>End:</b> ${gantt.templates.tooltip_date_format(e)}
            ${t.is_overdue ? '<br/><b style="color:#ef4444;">⚠️ OVERDUE</b>' : ''}`;
        gantt.templates.scale_cell_class = d => (d.getDay() === 0 || d.getDay() === 6) ? "weekend-header" : "";
        gantt.templates.timeline_cell_class = (t, d) => (d.getDay() === 0 || d.getDay() === 6) ? "weekend-cell" : "";

        gantt.attachEvent("onBeforeTaskDisplay", function(id, task) {
            var filters = document.querySelectorAll("input[wire\\:model='priorityFilter']");
            if (!task.priority) return true;

            for (var i = 0; i < filters.length; i++) {
                var f = filters[i];
                if (f.checked && task.priority === f.value) {
                    return true;
                }
            }
            return false;
        });

        document.querySelectorAll("input[wire\\:model='priorityFilter']").forEach(cb => {
            cb.addEventListener("change", () => {
                gantt.render();
            });
        });
    }

    function renderGantt(data, startDate, endDate) {
        if (!ganttPageInitialized) {
            initGanttConfig(startDate, endDate);
            gantt.init("gantt_here");

            const todayMarkerId = gantt.addMarker({
                start_date: new Date(),
                css: "today",
                text: "Today",
                title: "Hari ini"
            });

            setInterval(() => {
                const marker = gantt.getMarker(todayMarkerId);
                marker.start_date = new Date();
                gantt.updateMarker(todayMarkerId);
            }, 60000);

            ganttPageInitialized = true;
        } else {
            gantt.config.start_date = new Date(startDate);
            gantt.config.end_date = new Date(endDate);
        }

        gantt.clearAll();
        gantt.parse(data || {
            data: [],
            links: []
        });
        gantt.addMarker({
            start_date: new Date(),
            css: "today",
            text: "Today"
        });
        gantt.render();
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderGantt(ganttData, "{{ $start_date }}", "{{ $end_date }}");
    });

    document.addEventListener('livewire:init', () => {
        Livewire.on('refresh-gantt', payload => {
            ganttData = payload.ganttData;
            renderGantt(ganttData, payload.startDate, payload.endDate);
        });
    });
</script>
@endpush