<?php

namespace App\Http\Controllers;

use App\Models\LabelTemplate;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\File;

class LabelTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user  = User::where('name',$request->shop)->first();
        if(!$user){
            return response()->json([
                "status"=>false,
                "message"=>"Shop not found"

            ],404);
        }
        $defaultTemplates = LabelTemplate::whereNull('user_id')->get();



        foreach ($defaultTemplates as $template) {
            // 🔄 Update if exists or create a new template for the user
            LabelTemplate::firstOrCreate(
                ['user_id' => $user->id, 'name' => $template->name], // Check if exists
                ['content' => $template->content, 'type' => 'default'] // Create if not
            );
        }
    
        return response()->json(LabelTemplate::where('user_id', $user->id)->get());
    }

    public function getTemplates(Request $request){
        $user  = User::where('name',$request->shop)->first();
        if(!$user){
            return response()->json([
                "status"=>false,
                "message"=>"Shop not found"

            ],404);
        }

        $templates =LabelTemplate::where('user_id', $user->id)->get();

        return response()->json([
            "status"=>true,
            "templates"=>$templates
        ]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user  = User::where('name', $request->shop)->first();

        if(!$user){
            return response()->json([
                "status"=>false,
                "message"=>"Shop not found"

            ],404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                Rule::unique('label_templates')->where(fn($query) => $query->where('user_id',"!=",  $user->user_id)),
            ],
            'content' => 'required',
        ]);
        



        $validated['user_id'] = $user->id;

        $template = LabelTemplate::create($validated);


        return response()->json($template);
    }

    public function show($id){

        $template = LabelTemplate::find($id);

        return response()->json($template);   
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $template = LabelTemplate::findOrFail($id);

        $user  = User::where('name', $request->shop)->first();
        if(!$user){
            return response()->json([
                "status"=>false,
                "message"=>"Shop not found"

            ],404);
        }
        $validated = $request->validate([
            'name' => [
                'required',
                Rule::unique('label_templates')
                    ->where(fn($query) => $query->where('user_id',"!=", $user->user_id))
                    ->ignore($template->id), // Ignore current record
            ],
            'content' => 'required',
        ]);

        // dd($validated);
        $template->update($validated);

        return response()->json($template);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $template = LabelTemplate::findOrFail($id);
        $template->delete();

        return response()->json(['message' => 'Template deleted successfully.']);
    }

    /**
     * Generate PDF from the template.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function generatePdf($id)
    {
        $template = LabelTemplate::findOrFail($id);

        $data = [
            'order_name' => 'Order ABC',
            'order_number' => '12345',
            'item_number' => 'ITM-67890',
            'price' => '$99.99',
            'qr_code' => 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/qr_code.png')))
        ];

        $templateContent = str_replace(
            ['{order_name}', '{order_number}', '{item_number}', '{price}', '{qr_code}'],
            [$data['order_name'], $data['order_number'], $data['item_number'], $data['price'], $data['qr_code']],
            $template->content
        );
        $pdf = PDF::loadView('pdf.label_template', [
            'template' => $template,
            'templateContent' => $templateContent
        ]);
        $pdf = PDF::loadView('pdf.label_template', [
            'template' => $template,
            'templateContent' => $templateContent
        ]);

        return $pdf->stream('label_template_' . $template->id . '.pdf');
    }
}
