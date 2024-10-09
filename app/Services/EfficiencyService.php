<?php

namespace App\Services;

class EfficiencyService
{
    /** @var string  */
    const CRITERIA_G1 = 'g1';
    const CRITERIA_G2 = 'g2';
    const CRITERIA_G3 = 'g3';
    const CRITERIA_G4 = 'g4';
    const CRITERIA_G5 = 'g5';

    /** @var array */
    const AVAILABLE_CRITERIA = [
        self::CRITERIA_G1 => ['index' => 1],
        self::CRITERIA_G2 => ['index' => 2],
        self::CRITERIA_G3 => ['index' => 3],
        self::CRITERIA_G4 => ['index' => 4],
        self::CRITERIA_G5 => ['index' => 5],
    ];

    const AVAILABLE_MIN_MAX_BY_CRITERIA = [
        self::CRITERIA_G1 => ['min' => 20, 'max' => 115],
        self::CRITERIA_G2 => ['min' => 15, 'max' => 60],
        self::CRITERIA_G3 => ['min' => 10, 'max' => 50],
        self::CRITERIA_G4 => ['min' => 50, 'max' => 225],
        self::CRITERIA_G5 => ['min' => 25, 'max' => 90],
    ];

    /** @var string  */
    const TERM_U_1 = 'U1';
    const TERM_U_2 = 'U2';
    const TERM_U_3 = 'U3';
    const TERM_U_4 = 'U4';
    const TERM_U_5 = 'U5';

    /** @var array */
    const AVAILABLE_TERMS = [
        self::TERM_U_1,
        self::TERM_U_2,
        self::TERM_U_3,
        self::TERM_U_4,
        self::TERM_U_5,
    ];

    /** @var string */
    const RATING_M_1 = 'm1';
    const RATING_M_2 = 'm2';
    const RATING_M_3 = 'm3';
    const RATING_M_4 = 'm4';
    const RATING_M_5 = 'm5';

    /** @var array */
    const AVAILABLE_RATING_DATA = [
        self::RATING_M_1 => [
            'title' => 'Оцінка ідеї дуже низька',
            'bounds' => ['min' => 0, 'max' => 0.21],
        ],
        self::RATING_M_2 => [
            'title' => 'Оцінка ідеї низька',
            'bounds' => ['min' => 0.21, 'max' => 0.36],
        ],
        self::RATING_M_3 => [
            'title' => 'Оцінка ідеї середня',
            'bounds' => ['min' => 0.36, 'max' => 0.47],
        ],
        self::RATING_M_4 => [
            'title' => 'Оцінка ідеї вище середнього',
            'bounds' => ['min' => 0.47, 'max' => 0.67],
        ],
        self::RATING_M_5 => [
            'title' => 'Оцінка ідеї висока',
            'bounds' => ['min' => 0.67, 'max' => 1],
        ],
    ];

    /**
     * @param array $data
     * @return array
     */
    public function calculate(array $data): array
    {
        $score = 0;
        $weightSum = array_sum(array_column($data, 'weight'));
        foreach ($data as $criteria => &$criteriaData) {
            $criteriaData['belongFuncScore'] = $this->calculateBelongFunctionByScore($criteria, $criteriaData['score']);
            $criteriaData['belongFuncWantedScore'] = $this->calculateBelongFunctionByScore($criteria, $criteriaData['wantedScore']);
            $criteriaData['terms'] = $this->getUTerms($criteriaData['belongFuncScore'], $criteriaData['belongFuncWantedScore']);
            $criteriaData['belongFuncTerms'] = $this->calculateBelongFuncTerm($criteriaData['terms'], $criteriaData['wantedTerm']);
            $criteriaData['normalizedWeight'] = $criteriaData['weight'] / $weightSum;

            $score += $criteriaData['belongFuncTerms'] * $criteriaData['normalizedWeight'];
        }

        return [
            'data' => $data,
            'result' => $this->getScore($score),
        ];
    }

    /**
     * @param string $criteria
     * @param int $score
     * @return float
     */
    private function calculateBelongFunctionByScore(string $criteria, int $score): float
    {
        $minMaxData = self::AVAILABLE_MIN_MAX_BY_CRITERIA[$criteria];

        if ($score <= $minMaxData['min']) {
            return 0;
        } elseif ($score <= (($minMaxData['min'] + $minMaxData['max']) / 2)) {
            return round(2 * pow(($score - $minMaxData['min']) / ($minMaxData['max'] - $minMaxData['min']), 2), 2);
        } elseif ($score < $minMaxData['max']) {
            return round(1 - (2 * pow(($minMaxData['max'] - $score) / ($minMaxData['max'] - $minMaxData['min']), 2)), 2);
        } else {
            return 1;
        }
    }

    /**
     * @param float $x
     * @param float $a
     * @return array
     */
    private function getUTerms(float $x, float $a): array
    {
        $result = [];

        if ($x <= ($a - $a/2)) {
            $result[self::TERM_U_1] = 1;
        } elseif ($x > ($a - $a/2) && $x <= ($a - $a/4)) {
            $result[self::TERM_U_1] = round((3 * $a - 4 * $x) / $a, 2);
            $result[self::TERM_U_2] = round((4 * $x - 2 * $a) / $a, 2);
        } elseif ($x > ($a - $a/4) && $x <= $a) {
            $result[self::TERM_U_2] = round((4 * $a - 4 * $x) / $a, 2);
            $result[self::TERM_U_3] = round((4 * $x - 3 * $a) / $a, 2);
        } elseif ($x > $a && $x <= ($a + $a/4)) {
            $result[self::TERM_U_3] = round((5 * $a - 4 * $x) / $a,2);
            $result[self::TERM_U_4] = round((4 * $x - 4 * $a) / $a, 2);
        } elseif ($x > ($a + $a/4) && $x <= ($a + $a/2)) {
            $result[self::TERM_U_4] = round((6 * $a - 4 * $x) / $a, 2);
            $result[self::TERM_U_5] = round((4 * $x - 5 * $a) / $a, 2);
        } else {
            $result[self::TERM_U_5] = 1;
        }

        return $result;
    }

    /**
     * @param array $terms
     * @param string $wantedTerm
     * @return float
     */
    private function calculateBelongFuncTerm(array $terms, string $wantedTerm): float
    {
        $a = in_array($wantedTerm, array_keys($terms)) ? $terms[$wantedTerm] : 0;

        $index = array_search($wantedTerm, self::AVAILABLE_TERMS);

        $previous = $index > 0 ? self::AVAILABLE_TERMS[$index - 1] : null;
        $next = $index < count(self::AVAILABLE_TERMS) - 1 ? self::AVAILABLE_TERMS[$index + 1] : null;

        $b = 0;
        if (isset($terms[$previous])) {
            $b = round($terms[$previous] / 2, 3);
        } elseif (isset($terms[$next])) {
            $b = round($terms[$next] / 2, 3);
        }

        return max($a, $b);
    }

    /**
     * @param float $score
     * @return array
     */
    private function getScore(float $score): array
    {
        foreach (self::AVAILABLE_RATING_DATA as $key => $data) {
            $bounds = $data['bounds'];
            if ($score > $bounds['min'] && $score <= $bounds['max']) {
                return [
                    'key' => $key,
                    'title' => $data['title'],
                    'score' => round($score, 3),
                ];
            }
        }

        return [];
    }
}
