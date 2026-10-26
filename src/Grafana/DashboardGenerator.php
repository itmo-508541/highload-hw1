<?php

declare(strict_types=1);

namespace App\Grafana;

use Grafana\Foundation\Common\Constants as CommonConstants;
use Grafana\Foundation\Common\DataSourceRef;
use Grafana\Foundation\Common\LegendDisplayMode;
use Grafana\Foundation\Common\LegendPlacement;
use Grafana\Foundation\Common\VizLegendOptionsBuilder;
use Grafana\Foundation\Dashboard\DashboardBuilder;
use Grafana\Foundation\Dashboard\QueryVariableBuilder;
use Grafana\Foundation\Dashboard\VariableRefresh;
use Grafana\Foundation\Dashboard\VariableSort;
use Grafana\Foundation\Prometheus\DataqueryBuilder;
use Grafana\Foundation\Timeseries\PanelBuilder;
use Grafana\Foundation\Units\Constants as Units;

/**
 * @see https://github.com/grafana/grafana-foundation-sdk/blob/main/php/docs/How-To/building-a-dashboard.md
 */
class DashboardGenerator
{
    private const string DATASOURCE_TYPE = 'prometheus';

    private const string DATASOURCE_UID = 's1-prom';

    private const string QUERY_MIN_INTERVAL = '15s';

    private const string STATUS_OK = 'status_code=~"[123].."';

    private const string STATUS_ERROR = 'status_code=~"[45].."';

    private string $selector;

    public function generate(string $controller): \stdClass
    {
        $this->selector = \sprintf(
            'job="otelcollector",controller="%s",datacenter=~"$datacenter",pod_id=~"$pod_id"',
            $controller,
        );

        return new DashboardBuilder(title: $controller)
            ->uid('web_'.$controller)
            ->tags(['highload', 'web', 'otel'])
            ->editable()
            ->refresh('5s')
            ->time('now-15m', 'now')
            ->timezone(CommonConstants::TIME_ZONE_BROWSER)
            ->withVariable($this->variable(
                'datacenter',
                'datacenter',
                \sprintf('label_values(web_throughput_total{controller="%s"}, datacenter)', $controller),
            ))
            ->withVariable($this->variable(
                'pod_id',
                'pod',
                \sprintf('label_values(web_throughput_total{controller="%s",datacenter=~"$datacenter"}, pod_id)', $controller),
            ))
            ->withPanel(
                $this->timeseries(1, 'Latency (ok)', Units::MILLISECONDS)
                    ->description('Ответы 1xx, 2xx, 3xx. Перцентили интерполируются внутри бакетов гистограммы, это не точные значения.')
                    /* @phpstan-ignore argument.type */
                    ->withTarget($this->query(
                        'A',
                        $this->quantile('0.50', self::STATUS_OK),
                        'p50',
                    ))
                    /* @phpstan-ignore argument.type */
                    ->withTarget($this->query(
                        'B',
                        $this->quantile('0.95', self::STATUS_OK),
                        'p95',
                    ))
                    /* @phpstan-ignore argument.type */
                    ->withTarget($this->query(
                        'C',
                        $this->quantile('0.99', self::STATUS_OK),
                        'p99',
                    ))
            )
            ->withPanel(
                $this->timeseries(2, 'Latency (error)', Units::MILLISECONDS)
                    ->description('Ответы 4xx, 5xx. Перцентили интерполируются внутри бакетов гистограммы, это не точные значения.')
                    /* @phpstan-ignore argument.type */
                    ->withTarget($this->query(
                        'A',
                        $this->quantile('0.50', self::STATUS_ERROR),
                        'p50',
                    ))
                    /* @phpstan-ignore argument.type */
                    ->withTarget($this->query(
                        'B',
                        $this->quantile('0.95', self::STATUS_ERROR),
                        'p95',
                    ))
                    /* @phpstan-ignore argument.type */
                    ->withTarget($this->query(
                        'C',
                        $this->quantile('0.99', self::STATUS_ERROR),
                        'p99',
                    ))
            )
            ->withPanel(
                $this->timeseries(3, 'RPS', Units::REQUESTS_PER_SECOND)
                    ->description('Все завершившиеся запросы, любой status_code.')
                    /* @phpstan-ignore argument.type */
                    ->withTarget($this->query(
                        'A',
                        \sprintf('sum(rate(web_throughput_total{%s}[$__rate_interval]))', $this->selector),
                        'rps',
                    ))
            )
            ->withPanel(
                $this->timeseries(4, 'Ошибки по кодам ответа', Units::REQUESTS_PER_SECOND)
                    ->description('Запросы в секунду с ответами 4xx, 5xx, отдельная линия на каждый код.')
                    /* @phpstan-ignore argument.type */
                    ->withTarget($this->query(
                        'A',
                        \sprintf('sum by (status_code) (rate(web_throughput_total{%s,%s}[$__rate_interval]))', $this->selector, self::STATUS_ERROR),
                        '{{status_code}}',
                    ))
            )
            ->build()
            ->jsonSerialize();
    }

    private function datasource(): DataSourceRef
    {
        return new DataSourceRef(type: self::DATASOURCE_TYPE, uid: self::DATASOURCE_UID);
    }

    private function variable(string $name, string $label, string $query): QueryVariableBuilder
    {
        return new QueryVariableBuilder($name)
            ->label($label)
            ->datasource($this->datasource())
            ->query($query)
            ->definition($query)
            ->refresh(VariableRefresh::onTimeRangeChanged())
            ->sort(VariableSort::alphabeticalAsc())
            ->multi(true)
            ->includeAll(true)
            ->allValue('.*');
    }

    private function timeseries(int $id, string $title, string $unit): PanelBuilder
    {
        return new PanelBuilder()
            ->id($id)
            ->title($title)
            ->datasource($this->datasource())
            ->span(12)
            ->height(8)
            ->unit($unit)
            ->min(0)
            ->legend(
                new VizLegendOptionsBuilder()
                    ->showLegend(true)
                    ->displayMode(LegendDisplayMode::list())
                    ->placement(LegendPlacement::bottom())
            );
    }

    private function query(string $refId, string $expr, string $legend): DataqueryBuilder
    {
        return new DataqueryBuilder()
            ->refId($refId)
            ->datasource($this->datasource())
            ->expr($expr)
            ->legendFormat($legend)
            ->interval(self::QUERY_MIN_INTERVAL);
    }

    private function quantile(string $quantile, string $statusFilter): string
    {
        return \sprintf(
            'histogram_quantile(%s, sum by (le) (rate(web_latency_milliseconds_bucket{%s,%s}[$__rate_interval])))',
            $quantile,
            $this->selector,
            $statusFilter,
        );
    }
}
