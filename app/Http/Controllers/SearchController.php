<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;

class SearchController extends Controller
{
    use ApiResponseTrait;

    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return $this->unifiedResponse(false, 'Search query is required.', [], [], 400);
        }

        return $this->searchService->search($query);
    }


    public function advancedSearch(Request $request)
    {
        return $this->searchService->advancedSearch($request);
    }


    public function searchSpecialties(Request $request)
    {
        $request->merge(['type' => 'specialties']);
        return $this->searchService->advancedSearch($request);
    }


    public function searchDoctors(Request $request)
    {
        $request->merge(['type' => 'doctors']);
        return $this->searchService->advancedSearch($request);
    }

    
    public function searchCenters(Request $request)
    {
        $request->merge(['type' => 'centers']);
        return $this->searchService->advancedSearch($request);
    }
}
