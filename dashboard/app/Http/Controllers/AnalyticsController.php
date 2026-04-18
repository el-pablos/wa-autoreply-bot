<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsDailySummary;
use App\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->integer('days', 14);
        if (!in_array($days, [7, 14, 30], true)) {
            $days = 14;
        }

        $from = now()->subDays($days - 1)->startOfDay();

        $rangeQuery = MessageLog::query()->where('received_at', '>=', $from);

        $messagesIn = (clone $rangeQuery)->count();
        $messagesOut = (clone $rangeQuery)->where('replied', true)->count();
        $blockedCount = (clone $rangeQuery)->where('is_allowed', false)->count();

        $avgResponseMs = (int) round((float) ((clone $rangeQuery)->whereNotNull('response_time_ms')->avg('response_time_ms') ?? 0));

        $p95ResponseMs = $this->calculateP95(
            (clone $rangeQuery)
                ->whereNotNull('response_time_ms')
                ->pluck('response_time_ms')
                ->map(fn ($value) => (int) $value)
        );

        $replyRate = $messagesIn > 0 ? round(($messagesOut / $messagesIn) * 100, 2) : 0;

        $dailyRows = (clone $rangeQuery)
            ->selectRaw('DATE(received_at) as date, COUNT(*) as messages_in, SUM(CASE WHEN replied = 1 THEN 1 ELSE 0 END) as messages_out')
            ->groupBy(DB::raw('DATE(received_at)'))
            ->orderBy('date')
            ->get();

        $daily = collect();
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i)->toDateString();
            $found = $dailyRows->firstWhere('date', $date);

            $daily->push([
                'date' => $date,
                'messages_in' => (int) ($found->messages_in ?? 0),
                'messages_out' => (int) ($found->messages_out ?? 0),
            ]);
        }

        $topNumbers = (clone $rangeQuery)
            ->selectRaw('from_number, COUNT(*) as total')
            ->groupBy('from_number')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $hourly = array_fill(0, 24, 0);
        $heatmap = [];

        (clone $rangeQuery)
            ->whereNotNull('received_at')
            ->get(['received_at'])
            ->each(function (MessageLog $row) use (&$hourly, &$heatmap): void {
                if (!$row->received_at) {
                    return;
                }

                $weekday = (int) $row->received_at->dayOfWeekIso;
                $hour = (int) $row->received_at->format('G');

                $hourly[$hour] = (int) (($hourly[$hour] ?? 0) + 1);

                $heatmap[$weekday] ??= [];
                $heatmap[$weekday][$hour] = (int) (($heatmap[$weekday][$hour] ?? 0) + 1);
            });

        $dailySummary = AnalyticsDailySummary::query()
            ->orderByDesc('date')
            ->limit(14)
            ->get();

        return view('analytics.index', [
            'days' => $days,
            'messagesIn' => $messagesIn,
            'messagesOut' => $messagesOut,
            'replyRate' => $replyRate,
            'blockedCount' => $blockedCount,
            'avgResponseMs' => $avgResponseMs,
            'p95ResponseMs' => $p95ResponseMs,
            'daily' => $daily,
            'topNumbers' => $topNumbers,
            'hourly' => collect($hourly),
            'heatmap' => $heatmap,
            'dailySummary' => $dailySummary,
        ]);
    }

    private function calculateP95(Collection $values): int
    {
        $sorted = $values->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (int) $v)->sort()->values();

        $count = $sorted->count();
        if ($count === 0) {
            return 0;
        }

        $index = (int) ceil($count * 0.95) - 1;
        $index = max(0, min($index, $count - 1));

        return (int) $sorted->get($index, 0);
    }
}
