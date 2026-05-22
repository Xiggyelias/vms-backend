<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->input('category');

        $query = Report::query();

        if ($category) {
            $query->where('category', $category);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(20);

        if ($request->expectsJson() || $request->is('admin_reports.php')) {
            return $this->ok([
                'data' => $reports->items(),
                'pagination' => [
                    'current_page' => $reports->currentPage(),
                    'last_page' => $reports->lastPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                ],
            ], 'Reports fetched.');
        }

        return view('reports.index', compact('reports'));
    }

    public function create()
    {
        return view('reports.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:incident,maintenance,general',
            'report_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->fail('Validation failed.', 422, ['errors' => $validator->errors()]);
            }
            return back()->withErrors($validator)->withInput();
        }

        Report::create([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'category' => $request->input('category'),
            'report_date' => $request->input('report_date', now()->toDateString()),
            'admin_id' => session('admin_id'),
            'created_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return $this->ok([], 'Report created successfully.', 201);
        }

        return redirect()->route('reports.index')->with('success', 'Report created successfully.');
    }

    public function edit(Request $request, $id = null)
    {
        $id = $id ?? $request->query('id') ?? $request->input('report_id') ?? $request->input('id');
        if (!$id) {
            abort(404, 'Report not found.');
        }

        $report = Report::findOrFail($id);

        return view('reports.edit', compact('report'));
    }

    public function update(Request $request, $id = null)
    {
        $id = $id ?? $request->input('report_id') ?? $request->input('id');
        if (!$id) {
            if ($request->expectsJson()) {
                return $this->fail('Missing report ID.', 400);
            }
            return back()->with('error', 'Missing report ID.');
        }

        $report = Report::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:incident,maintenance,general',
            'report_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->fail('Validation failed.', 422, ['errors' => $validator->errors()]);
            }
            return back()->withErrors($validator)->withInput();
        }

        $report->update($request->only(['title', 'description', 'category', 'report_date']));

        if ($request->expectsJson()) {
            return $this->ok([], 'Report updated successfully.');
        }

        return redirect()->route('reports.index')->with('success', 'Report updated successfully.');
    }

    public function destroy(Request $request, $id = null)
    {
        $id = $id ?? $request->input('report_id') ?? $request->input('id');
        if (!$id) {
            return $this->fail('Missing report ID.', 400);
        }

        $report = Report::findOrFail($id);
        $report->delete();
        if ($request->expectsJson()) {
            return $this->ok([], 'Report deleted successfully.');
        }
        return redirect()->route('reports.index')->with('success', 'Report deleted successfully.');
    }
}
