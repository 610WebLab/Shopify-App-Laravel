<?php

namespace App\Http\Controllers;

use App\Models\LabelTemplate;
use App\Models\User;
use App\Services\Label\LabelHtmlRenderer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $this->syncDefaultTemplatesForShop($user->id);

        return response()->json(LabelTemplate::where('user_id', $user->id)->orderBy('id', 'ASC')->get());
    }

    public function getTemplates(Request $request){
        $user  = User::where('name',$request->shop)->first();
        if(!$user){
            return response()->json([
                "status"=>false,
                "message"=>"Shop not found"

            ],404);
        }

        $this->syncDefaultTemplatesForShop($user->id);

        $templates = LabelTemplate::where('user_id', $user->id)->orderBy('id', 'ASC')->get();

        return response()->json([
            "status"=>true,
            "templates"=>$templates
        ]);

    }

    /**
     * Copy system default templates (user_id null) into the shop account.
     */
    private function syncDefaultTemplatesForShop(int $userId): void
    {
        $defaultTemplates = LabelTemplate::whereNull('user_id')->where('type', 'default')->get();

        foreach ($defaultTemplates as $template) {
            LabelTemplate::firstOrCreate(
                [
                    'user_id' => $userId,
                    'name' => $template->name,
                ],
                [
                    'content' => $template->content,
                    'type' => 'default',
                ]
            );
        }
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
     * Generate PDF preview using the shop's latest order and Label Settings.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function generatePdf(Request $request, $id)
    {
        $user = User::where('name', $request->shop)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Shop not found',
            ], 404);
        }

        $template = LabelTemplate::where('id', $id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)->orWhereNull('user_id');
            })
            ->firstOrFail();

        $renderer = app(LabelHtmlRenderer::class);
        $preview = $renderer->renderPreviewHtml($template, $user);

        $pdf = PDF::loadView('pdf.label_template', [
            'template' => $template,
            'templateContent' => $preview['html'],
            'orderLabel' => $preview['order_label'],
        ])->setPaper('letter');

        $fileName = 'label_preview_' . $template->id . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'X-Preview-Order' => $preview['order_label'],
            'Access-Control-Expose-Headers' => 'X-Preview-Order',
        ]);
    }
}
