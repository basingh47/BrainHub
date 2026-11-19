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
    public function index()
    {
        $routines = Routine::all();
        $parents = Routine::whereNull('parent_id')->get();

        return view('routines.index', [
            'routines' => $routines,
            'parents' => $parents,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
     * Display the specified resource.
     */
    public function show(Routine $routine)
    {
        //
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
     * Remove the specified resource from storage.
     */
    public function destroy(Routine $routine)
    {
        //
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
