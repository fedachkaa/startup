<?php

namespace App\Http\Controllers;

use App\Services\EfficiencyService;
use App\Services\RiskService;
use Illuminate\Http\Request;

class IndexController
{
    /** @var RiskService */
    private $riskService;

    /** @var EfficiencyService */
    private $efficiencyService;

    /**
     * @param RiskService $riskService
     * @param EfficiencyService $efficiencyService
     */
    public function __construct(RiskService $riskService, EfficiencyService $efficiencyService)
    {
        $this->riskService = $riskService;
        $this->efficiencyService = $efficiencyService;
    }

    public function index()
    {
        return view('index');
    }

    public function riskAssessment()
    {
        return view('risk-assessment');
    }

    public function calculateRiskAssessment(Request $request)
    {
        $data = $request->input('data');

        try {
            $result = $this->riskService->calculateRisks($data);
        } catch (\Throwable $e) {
            \logger('Error while calculating risks. Error: "' . $e->getMessage() . '".');

            return response()->json([
                'success' => false,
                'code' => 500,
                'message' => 'Internal server error.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function evaluationEfficiency()
    {
        return view('evaluation-of-efficiency');
    }

    public function calculateEvaluationEfficiency(Request $request)
    {
        $data = $request->input('data');

        try {
            $result = $this->efficiencyService->calculate($data);
        } catch (\Throwable $e) {
            \logger('Error while calculating efficiency. Error: "' . $e->getMessage() . '".');

            return response()->json([
                'success' => false,
                'code' => 500,
                'message' => 'Internal server error.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);

    }
}
