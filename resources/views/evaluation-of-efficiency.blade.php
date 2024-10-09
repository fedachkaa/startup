@extends('layouts.main')

@section('title', 'Оцінка ризиків стартап проекту')

@section('content')
    <table class="table table-bordered text-center align-middle">
        <thead>
        <tr class="table-primary">
            <th>Критерій</th>
            <th>Бальна оцінка</th>
            <th style="display: none">Функція належності бальної оцінки</th>
            <th>Бажані значення</th>
            <th style="display: none">Функція належності бажаних значень</th>
            <th style="display: none">Отримані терми</th>
            <th style="display: none">Достовірнісь терму</th>
            <th>Бажаний терм</th>
            <th style="display: none">Отримана оцінка</th>
            <th>Ваговий коефіцієнт</th>
        </tr>
        </thead>
        <tbody>
        @foreach(\App\Services\EfficiencyService::AVAILABLE_CRITERIA as $key => $criteriaData)
            <tr class="js-criteria-row" data-name="{{ $key }}">
                <td>G<sub>{{ $criteriaData['index'] }}</sub></td>
                <td><input type="number" min="0" name="{{ $key . '_' . $criteriaData['index'] }}_score" required></td>
                <td style="display: none;" class="js-belong-func-score"></td>
                <td><input type="number" min="0" name="{{ $key . '_' . $criteriaData['index'] }}_wanted_score" required></td>
                <td style="display: none;" class="js-belong-func-wanted-score"></td>
                <td style="display: none;" class="js-calculated-terms"></td>
                <td style="display: none;" class="js-belong-func-calculated-terms"></td>
                <td>
                    <select name="{{ $key . '_' . $criteriaData['index'] }}_wanted_term">
                        @foreach(\App\Services\EfficiencyService::AVAILABLE_TERMS as $term)
                            <option value="{{ $term }}">{{ $term }}</option>
                        @endforeach
                    </select>
                </td>
                <td style="display: none;" class="js-calculated-score"></td>
                <td class="js-weight">
                    <input type="number" min="0" max="10" step="1" name="{{ $key . '_' . $criteriaData['index'] }}_weight">
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        <button class="js-calculate btn btn-success">Обрахунок</button>
    </div>

    <div class="js-results" style="display: none">
        <h3>Оцінка: <span class="js-result-score"></span></h3>
        <h3>Терм: <span class="js-result-term"></span></h3>
    </div>

    <script>
        $(function() {
            initCalculate();
        });

        function initCalculate() {
            $('.js-calculate').on('click', function ()  {
                const data = {};
                $('.js-criteria-row').each(function () {
                    data[$(this).data('name')] = {
                        score: $(this).find('input[name*="_score"]').val(),
                        wantedScore: $(this).find('input[name*="_wanted_score"]').val(),
                        wantedTerm: $(this).find('select[name*="_wanted_term"]').val(),
                        weight: $(this).find('input[name*="_weight"]').val()
                    };
                });

                $.ajax({
                    url: '/evaluation-efficiency/calculate',
                    method: 'POST',
                    data: JSON.stringify({
                        _token: "{{ csrf_token() }}",
                        data: data,
                    }),
                    contentType: 'application/json',
                    success: function (response) {
                        displayData(response.data);
                    },
                    error: function (response) {
                        console.log(response);
                    }
                });
            });
        }

        function displayData(data) {
            const efficiencyData = data.data;
            const resultData = data.result;

            $('th').each(function() {
                $(this).css('display', '');
            });

            Object.keys(efficiencyData).forEach(key => {
                const targetRow = $('.js-criteria-row[data-name="' + key + '"]').first();
                targetRow.find('.js-belong-func-score').text(efficiencyData[key]['belongFuncScore']);
                targetRow.find('.js-belong-func-wanted-score').text(efficiencyData[key]['belongFuncWantedScore']);
                targetRow.find('.js-calculated-terms').text(Object.keys(efficiencyData[key]['terms']).join(' або '));
                targetRow.find('.js-belong-func-calculated-terms').text(Object.keys(efficiencyData[key]['terms']).map(termKey => `${termKey} = ${efficiencyData[key]['terms'][termKey]}`).join(' або '));
                targetRow.find('.js-calculated-score').text(efficiencyData[key]['belongFuncTerms']);
                targetRow.find('td').each(function() {
                    $(this).css('display', '');
                });
            });

            $('.js-result-score').text(resultData.score);
            $('.js-result-term').text(resultData.key + ' - ' + resultData.title);
            $('.js-results').css('display', '');
        }
    </script>
@stop
