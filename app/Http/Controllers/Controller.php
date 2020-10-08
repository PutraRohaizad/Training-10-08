<?php

namespace App\Http\Controllers;

use App\Pembeli;
use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index()
    {
        return view('layout');
    }

    public function semakan(Request $request)
    {   
        // Query Dulu
        $qpembeli = Pembeli::query();
        // Filter
        if($tarikhccc = $request->input('tarikhccc')){
            $qpembeli->where('tarikhccc' , 'like' , "%$tarikhccc%");
        }
        // Execute
        $pembeli_rs = $qpembeli->paginate(5);
        return view('semakan', compact('pembeli_rs'));
    }

    public function permohonan(Pembeli $pembeli)
    {   
        // dd($pembeli);
        return view('permohonan');
    }

    public function maklumat()
    {
        return view('maklumat');
    }
}
