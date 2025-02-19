<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComplainsRequest;
use App\Models\Heinous;
use App\Mail\NewComplain;
use App\Models\Agency;
use App\Models\Complains;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ComplainEmail;
use Illuminate\Support\Facades\Mail;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Crypt;

class ComplainsController extends Controller
{
    public function form($agency_id)
    {
        //$agency_id = Crypt::decrypt($agency_id);
        $agency_id = Agency::query()->first()->id;
        return view('complains', compact('agency_id'));
    }

    public function submit(ComplainsRequest $request)
    {
        $images = [];
        if ($request->file('image1')) {
            $images[] = $request->file('image1')->store('complains');
        }
        if ($request->file('image2')) {
            $images[] = $request->file('image2')->store('complains');
        }
        if ($request->file('image3')) {
            $images[] = $request->file('image3')->store('complains');
        }

        Complains::create([
            "first_name"           => $request->first_name,
            "middle_name"          => $request->middle_name,
            "last_name"            => $request->last_name,
            "gender"               => $request->gender,
            "passport"             => $request->passport,
            "location_ksa"         => $request->location_ksa,
            "email_address"        => $request->email_address,
            "contact_number"       => $request->contact_number,
            "contact_number2"      => $request->contact_number2,
            "occupation"           => $request->occupation,
            "employer_name"        => $request->employer_name,
            "employer_contact"     => $request->employer_contact,
            "agency"               => $request->agency,
            "agency_id"            => $request->agency_id,
            "complain"             => $request->complain,
            "actual_langitude"     => $request->actual_langitude,
            "actual_longitude"     => $request->actual_longitude,
            "national_id"          => $request->national_id,
            "contact_person"       => $request->contact_person,
            "employer_national_id" => $request->employer_national_id,
            "host_agency"          => $request->host_agency,
            "image1"               => ! isset($images[0]) ?: $images[0],
            "image2"               => ! isset($images[1]) ?: $images[1],
            "image3"               => ! isset($images[2]) ?: $images[2],
        ]);

        Mail::to(explode(',', env('COMPLAINT_RECEIVER')))
            ->bcc(explode(',', env('COMPLAINT_BCC')))
            ->send(new NewComplain($request));

        return view('success');
    }

    public function table(Request $request)
    {
        $complains = Complains::query()->with(['agencies'])->when($request->agency_id, function ($q) use ($request) {
            $q->where('agency_id', $request->agency_id);
        });

        return DataTables::of($complains)->setTransformer(function ($value) {
            $value->created_at_display = Carbon::parse($value->created_at)->format('F j, Y');
            $value->route_show         = route('complains.show', ['id' => $value->id]);

            return collect($value)->toArray();
        })->make(true);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('components.admin.complain');
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Complains $complains
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function show($id, Complains $complains)
    {
        $preview = $complains->where('id', $id)->first();

        return view('components.admin.complain-show', compact('preview'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Complains $complains
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($id, Request $request, Complains $complains)
    {
        $complains->where('id', $id)
                  ->update(['remarks' => $request->remarks]);

        return redirect()->route('complains.index');
    }

    public function getHeinousList(Request $request)
    {
        return Heinous::all();
    }

    public function storeHeinous(Request $request)
    {
        Heinous::create([
            'name'       => $request->name,
            'priority'   => $request->priority,
            'created_by' => auth()->user()->email,
        ]);

        return ['success' => true];
    }

    public function deleteHeinous(Request $request)
    {
        Heinous::destroy($request->id);

        return ['success' => true];
    }

    public function storeComplaintEmail(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
        ]);
        ComplainEmail::create([
            'email'      => $request->email,
            'created_by' => auth()->user()->email,
        ]);

        return ['success' => true];
    }

    public function getComplaintEmailList()
    {
        return ComplainEmail::all();
    }

    public function deleteComplaint(Request $request)
    {
        ComplainEmail::destroy($request->id);

        return ['success' => true];
    }
}
