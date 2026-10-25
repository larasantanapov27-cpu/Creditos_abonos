<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CatalogoController extends Controller
{
    public const MAP = [
        'estados' => ['id_estado', 'nombre_estado', 'Estados'],
        'municipios' => ['id_municipio', 'nombre_municipio', 'Municipios'],
        'tasa_intereses' => ['id_tasa_interes', 'tasa', 'Tasas de referencia'],
        'tipo_pagos' => ['id_tipo_pago', 'tipo', 'Tipos de pago'],
    ];

    private function config(string $tipo): array
    {
        abort_unless(isset(self::MAP[$tipo]), 404);

        return self::MAP[$tipo];
    }

    public function index(Request $r)
    {
        $tipo = isset(self::MAP[$r->tipo]) ? $r->tipo : 'estados';
        [$pk,$campo,$titulo] = $this->config($tipo);

        return view('catalogos.index', compact('tipo', 'pk', 'campo', 'titulo') + [
            'map' => self::MAP,
            'filas' => DB::table($tipo)->orderBy($pk)->paginate(12)->withQueryString(),
            'estados' => DB::table('estados')->orderBy('nombre_estado')->get(),
        ]);
    }

    private function data(Request $r, string $tipo): array
    {
        [, $campo] = $this->config($tipo);
        $rules = [
            'valor' => $tipo === 'tasa_intereses' ? 'required|numeric|min:0|max:999.9999|decimal:0,4' : 'required|string|max:'.($tipo === 'tipo_pagos' ? 25 : 50),
        ];
        if ($tipo === 'municipios') {
            $rules['id_estado'] = 'required|exists:estados,id_estado';
        }
        $d = $r->validate($rules);

        return [$campo => $d['valor']] + ($tipo === 'municipios' ? ['id_estado' => $d['id_estado']] : []);
    }

    private function used(string $tipo, string $pk, int $id): bool
    {
        return match ($tipo) {
            'estados' => DB::table('registro_clientes')->where($pk, $id)->exists() || DB::table('municipios')->where($pk, $id)->exists(),
            'municipios' => DB::table('registro_clientes')->where($pk, $id)->exists(),
            default => DB::table('registro_creditos')->where($pk, $id)->exists(),
        };
    }

    public function store(Request $r, string $tipo)
    {
        $this->config($tipo);
        DB::table($tipo)->insert($this->data($r, $tipo));

        return back()->with('success', 'Elemento agregado al catálogo.');
    }

    public function update(Request $r, string $tipo, int $id)
    {
        [$pk] = $this->config($tipo);
        $d = $this->data($r, $tipo);
        DB::transaction(function () use ($tipo, $pk, $id, $d) {
            abort_unless(DB::table($tipo)->where($pk, $id)->lockForUpdate()->first(), 404);
            if ($this->used($tipo, $pk, $id)) {
                throw ValidationException::withMessages(['catalogo' => 'Este elemento está utilizado. Agrega otro para conservar el historial.']);
            } DB::table($tipo)->where($pk, $id)->update($d);
        });

        return back()->with('success', 'Catálogo actualizado.');
    }

    public function destroy(Request $r, string $tipo, int $id)
    {
        [$pk] = $this->config($tipo);
        DB::transaction(function () use ($tipo, $pk, $id) {
            abort_unless(DB::table($tipo)->where($pk, $id)->lockForUpdate()->first(), 404);
            if ($this->used($tipo, $pk, $id)) {
                throw ValidationException::withMessages(['catalogo' => 'No se puede eliminar un elemento utilizado.']);
            } DB::table($tipo)->where($pk, $id)->delete();
        });

        return back()->with('success', 'Elemento eliminado.');
    }
}
