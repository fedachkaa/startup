@extends('layouts.main')

@section('title', 'Оцінка ризиків стартап проекту')

@section('content')
<table class="table table-bordered text-center align-middle">
    <thead>
    <tr class="table-primary">
        <th>№</th>
        <th>Критерій</th>
        <th>Значення лінгвістичних змінних</th>
        <th>Достовірність</th>
        <th style="display: none">Результуюча терм-оцінка</th>
        <th style="display: none">Агрегована оцінка</th>
        <th style="display: none">X</th>
        <th style="display: none">Z</th>
    </tr>
    </thead>
    <tbody>
        @foreach(\App\Services\RiskService::AVAILABLE_CRITERIA as $key => $criteriaData)
            <tr class="table-warning">
                <td colspan="8" style="text-align: center; font-weight: bold;">K<sup>{{ $criteriaData['index'] }}</sup> - {{ $criteriaData['title'] }}</td>
            </tr>
            @for($i = 1; $i <= $criteriaData['count']; $i++)
                <tr class="js-criteria-row" data-name="{{ $key }}">
                    <td>{{ $i }}</td>
                    <td>K<sup>O</sup><sub>{{ $i }}</sub></td>
                    <td>
                        <select name="<?= $key;?>_ling_value_<?= $i; ?>">
                            @foreach(\App\Services\RiskService::AVAILABLE_LING_VALUES as $lingKey => $lingName)
                                <option value="{{ $lingKey }}">{{ $lingName }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" step="0.1" min="0" max="1" name="{{ $key }}_certainty_{{ $i }}" required></td>
                    <td style="display: none;" rowspan="{{ $criteriaData['count'] }}" class="js-result-term"></td>
                    <td style="display: none;" rowspan="{{ $criteriaData['count'] }}" class="js-agg-rating"></td>
                    <td style="display: none;" rowspan="{{ $criteriaData['count'] }}" class="js-x"></td>
                    <td style="display: none;" rowspan="{{ $criteriaData['count'] }}" class="js-z"></td>
                </tr>
            @endfor
        @endforeach
    </tbody>
</table>
<div class="d-flex justify-content-center">
    <button class="js-calculate btn btn-success">Обрахунок</button>
</div>
<div class="js-results" style="display: none">
    <h3>Агрегована оцінка ризику: <span class="js-total-agg-level"></span></h3>
    <h3>Рівень безпеки фінансування проекту: <span class="js-ling-total-level"></span></h3>
</div>

<script>
    $(function() {
        initCalculate();
    });

    function initCalculate() {
        $('.js-calculate').on('click', function ()  {
            const data = {};
            $('.js-criteria-row').each(function () {
                const criteriaName = $(this).data('name');

                data[criteriaName] = data[criteriaName] || [];

                const lingValue = $(this).find('select[name*="_ling_value_"]').val();
                const certaintyValue = $(this).find('input[name*="_certainty_"]').val();

                data[criteriaName].push({
                    lingValue: lingValue,
                    certainty: certaintyValue
                });
            });

            $.ajax({
                url: '/risk-assessment/calculate',
                method: 'POST',
                data: JSON.stringify({
                    _token: "{{ csrf_token() }}",
                    data: data,
                }),
                contentType: 'application/json',
                success: function (response) {
                    displayResults(response.data);
                },
                error: function (response) {
                    console.log(response);
                }
            });
        });
    }

    function displayResults(results) {
        $('th').each(function() {
            $(this).css('display', '');
        });

        const risksData = results.data;
        Object.keys(risksData).forEach(key => {
            const targetRow = $('.js-criteria-row[data-name="' + key + '"]').first();
            targetRow.find('.js-result-term').text(risksData[key]['stepOne']);
            targetRow.find('.js-agg-rating').text(risksData[key]['stepTwo']);
            targetRow.find('.js-x').text(risksData[key]['stepThree']['x']);
            targetRow.find('.js-z').text(risksData[key]['stepThree']['z']);
            targetRow.find('td').each(function() {
                $(this).css('display', '');
            });
        });

        $('.js-total-agg-level').text(results.result.totalAggLevel);
        $('.js-ling-total-level').text(results.result.lingTotalLevel);
        $('.js-results').css('display', '');
    }
</script>
@stop
