<?php

namespace App\Http\Controllers;

use App\Services\RiskService;
use Illuminate\Http\Request;

class IndexController
{
    /** @var RiskService */
    private $riskService;

    /**
     * @param RiskService $riskService
     */
    public function __construct(RiskService $riskService)
    {
        $this->riskService = $riskService;
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
            'message' => 'Data received successfully.',
            'data' => $result,
        ]);

    }
}
