<?php

namespace App\Services;

class RiskService
{
    /** @var string  */
    const OPERATIONAL_RISKS = 'operational_risks';
    const INVESTMENT_RISKS = 'investment_risks';
    const FINANCIAL_RISKS = 'financial_risks';
    const INNOVATIVE_ACTIVITY_RISKS = 'innovative_activity_risks';

    /** @var array */
    const AVAILABLE_CRITERIA = [
        self::OPERATIONAL_RISKS => [
            'index' => 'O',
            'title' => 'Операційні ризики',
            'count' => 9,
        ],
        self::INVESTMENT_RISKS => [
            'index' => 'I',
            'title' => 'Інвестиційні ризики',
            'count' => 5,
        ],
        self::FINANCIAL_RISKS => [
            'index' => 'F',
            'title' => 'Фінансові ризики',
            'count' => 5,
        ],
        self::INNOVATIVE_ACTIVITY_RISKS => [
            'index' => 'S',
            'title' => 'Ризики інноваційної діяльності',
            'count' => 5,
        ],
    ];

    /** @var string  */
    const RISK_LEVEL_LOW = 'low';
    const RISK_LEVEL_BELOW_AVERAGE = 'below_avg';
    const RISK_LEVEL_AVERAGE = 'avg';
    const RISK_LEVEL_ABOVE_AVERAGE = 'above_avg';
    const RISK_LEVEL_HIGH = 'high';

    /** @var array */
    const AVAILABLE_LING_VALUES = [
        self::RISK_LEVEL_LOW => 'Н',
        self::RISK_LEVEL_BELOW_AVERAGE => 'HC',
        self::RISK_LEVEL_AVERAGE => 'C',
        self::RISK_LEVEL_ABOVE_AVERAGE => 'BC',
        self::RISK_LEVEL_HIGH => 'B',
    ];

    /** @var array */
    const AVAILABLE_PERCENTAGES_BY_TERM = [
        self::RISK_LEVEL_LOW => ['a' => 0, 'b' => 20],
        self::RISK_LEVEL_BELOW_AVERAGE => ['a' => 20, 'b' => 40],
        self::RISK_LEVEL_AVERAGE => ['a' => 40, 'b' => 60],
        self::RISK_LEVEL_ABOVE_AVERAGE => ['a' => 60, 'b' => 80],
        self::RISK_LEVEL_HIGH => ['a' => 80, 'b' => 100],
    ];

    /** @var string */
    const RESULTED_RISK_LEVEL_R1 = 'r1';
    const RESULTED_RISK_LEVEL_R2 = 'r2';
    const RESULTED_RISK_LEVEL_R3 = 'r3';
    const RESULTED_RISK_LEVEL_R4 = 'r4';
    const RESULTED_RISK_LEVEL_R5 = 'r5';

    /** @var array */
    const AVAILABLE_RESULTED_RISK_LEVELS = [
        self::RESULTED_RISK_LEVEL_R1 => 'R1 - незначний ступінь ризику проекту',
        self::RESULTED_RISK_LEVEL_R2 => 'R2 - низький ступінь ризику проекту',
        self::RESULTED_RISK_LEVEL_R3 => 'R3 - середній ступінь ризику проекту',
        self::RESULTED_RISK_LEVEL_R4 => 'R4 - високий ступінь ризику проекту',
        self::RESULTED_RISK_LEVEL_R5 => 'R5 - граничний ступінь ризику проекту',
    ];

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function calculateRisks(array $data): array
    {
        $allZ = [];

        foreach ($data as $criteriaName => $values) {
            $aggValues = $this->calculateAggValuesForRiskType($values);
            $xAndZ = $this->getXandZ($aggValues['resultTerm'], $aggValues['aggCertainty']);
            $data[$criteriaName]['stepOne'] = self::AVAILABLE_LING_VALUES[$aggValues['resultTerm']];
            $data[$criteriaName]['stepTwo'] = $aggValues['aggCertainty'];
            $data[$criteriaName]['stepThree'] = $xAndZ;
            $allZ[] = $xAndZ['z'];
        }

        $totalAggLevel = $this->getTotalAggLevel($allZ);

        return [
            'data' => $data,
            'result' => [
                'totalAggLevel' => $totalAggLevel,
                'lingTotalLevel' => self::AVAILABLE_RESULTED_RISK_LEVELS[$this->getLinguisticTotalLevel($totalAggLevel)],
            ],
        ];
    }

    /**
     * @param array $valuesForRiskType
     * @return array
     * @throws \Exception
     */
    private function calculateAggValuesForRiskType(array $valuesForRiskType): array
    {
        $totalCriteria = count($valuesForRiskType);

        $counts = [
            self::RISK_LEVEL_LOW => 0,
            self::RISK_LEVEL_BELOW_AVERAGE => 0,
            self::RISK_LEVEL_AVERAGE => 0,
            self::RISK_LEVEL_ABOVE_AVERAGE => 0,
            self::RISK_LEVEL_HIGH => 0,
        ];

        foreach ($valuesForRiskType as $data) {
            if (!in_array($data['lingValue'], array_keys(self::AVAILABLE_LING_VALUES))) {
                throw new \Exception('Undefined linguistic value');
            }
            $counts[$data['lingValue']]++;
        }

        $resultLevel = null;
        if ($counts[self::RISK_LEVEL_LOW] / $totalCriteria >= 0.6) {
            $resultLevel = self::RISK_LEVEL_LOW;
        } elseif ($counts[self::RISK_LEVEL_BELOW_AVERAGE] / $totalCriteria >= 0.6 && ($counts[self::RISK_LEVEL_LOW] + $counts[self::RISK_LEVEL_BELOW_AVERAGE]) / $totalCriteria >= 0.4) {
            $resultLevel = self::RISK_LEVEL_BELOW_AVERAGE;
        } elseif ($counts[self::RISK_LEVEL_AVERAGE] / $totalCriteria >= 0.6 && ($counts[self::RISK_LEVEL_BELOW_AVERAGE] + $counts[self::RISK_LEVEL_AVERAGE]) / $totalCriteria >= 0.4) {
            $resultLevel = self::RISK_LEVEL_AVERAGE;
        } elseif ($counts[self::RISK_LEVEL_ABOVE_AVERAGE] / $totalCriteria >= 0.6 && ($counts[self::RISK_LEVEL_AVERAGE] + $counts[self::RISK_LEVEL_ABOVE_AVERAGE]) / $totalCriteria >= 0.4) {
            $resultLevel = self::RISK_LEVEL_ABOVE_AVERAGE;
        } elseif ($counts[self::RISK_LEVEL_HIGH] / $totalCriteria >= 0.6) {
            $resultLevel = self::RISK_LEVEL_HIGH;
        }

        $sumOfCertainties = 0;
        foreach ($valuesForRiskType as $data) {
            if ($data['lingValue'] === $resultLevel) {
                $sumOfCertainties += $data['certainty'];
            }
        }

        return [
            'resultTerm' => $resultLevel,
            'aggCertainty' => round((1 / $counts[$resultLevel]) * $sumOfCertainties, 1),
        ];
    }

    /**
     * @param string $riskLevel
     * @param float $certainty
     * @return array
     */
    private function getXandZ(string $riskLevel, float $certainty): array
    {
        $percentages = self::AVAILABLE_PERCENTAGES_BY_TERM[$riskLevel];
        if ($certainty <= 0.5) {
            $xa = sqrt($certainty/2) * ($percentages['b'] - $percentages['a']) + $percentages['a'];
        } else {
            $xa = $percentages['b'] - sqrt((1 - $certainty)/2) * ($percentages['b'] - $percentages['a']);
        }

        $za = $xa / 100;

        return [
            'x' => round($xa, 1),
            'z' => round($za, 2),
        ];
    }

    /**
     * @param array $data
     * @return float
     */
    private function getTotalAggLevel(array $data): float
    {
        $sum = 0;
        foreach ($data as $item) {
            $sum += (1 - $item);
        }

        return round($sum / 4, 2);
    }

    /**
     * @param float $result
     * @return string
     */
    private function getLinguisticTotalLevel(float $result): string
    {
        if ($result > 0.87 && $result <= 1) {
            return self::RESULTED_RISK_LEVEL_R1;
        } elseif ($result > 0.67 && $result <= 0.87) {
            return self::RESULTED_RISK_LEVEL_R2;
        } elseif ($result > 0.36 && $result <= 0.67) {
            return self::RESULTED_RISK_LEVEL_R3;
        } elseif ($result > 0.21 && $result <= 0.36) {
            return self::RESULTED_RISK_LEVEL_R4;
        } else {
            return self::RESULTED_RISK_LEVEL_R5;
        }
    }
}
