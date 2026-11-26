<?php

namespace App\Http\Controllers;

use App\Models\Routine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoutineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $routines = $request->user()->routines()          // ← only MY routines
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('target_time')
            ->withCount(['weeks', 'checks'])  // ← for isUsed() accessor
            ->get();
        $parents = $routines->where('is_active', true);

        return view('routines.manage-routines.manage-routines', [
            'routines' => $routines,
            'parents' => $parents,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $routine = Routine::create($request->all());

        return redirect()->route('routines.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Routine $routine)
    {
        return view('routines.edit', [
            'routine' => $routine,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Routine $routine)
    {
        $routine->update($request->all());

        return redirect()->route('routines.index');
    }

    /**
     * Archive a routine: it stays on old sheets (history kept) but is not offered for new weeks.
     */
    public function deactivate(Routine $routine): RedirectResponse
    {
        $routine->update(['is_active' => false]);

        return redirect()->route('routines.index')
            ->with('flash', ['type' => 'info', 'message' => "\"{$routine->name}\" deactivated."]);
    }

    /**
     * Bring an archived routine back.
     */
    public function activate(Routine $routine): RedirectResponse
    {
        $routine->update(['is_active' => true]);

        return redirect()->route('routines.index')
            ->with('flash', ['type' => 'success', 'message' => "\"{$routine->name}\" activated."]);
    }
}
