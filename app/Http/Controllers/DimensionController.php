<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Dimension;
class DimensionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $shop = User::where('name', $request->shop)->first();

        if ($shop) {
            // Use pagination only if 'page' parameter is present
            if ($request->has('page')) {
                // $boxDimension = PackageDimension::where('user_id', $shop->id)->orderBy('sorting', 'ASC')->first();
                $dimensions = Dimension::where('user_id', $shop->id)
                    ->orderBy('sorting', 'ASC')
                    ->paginate(50);
            } else {
                $dimensions = Dimension::where('user_id', $shop->id)
                    ->orderBy('sorting', 'ASC')
                    ->get();
            }

            return response()->json([
                'status' => true,
                'data' => $dimensions
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => "Shop not found"
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // dd($request->all());
            // Retrieve the shop based on the shop name from the request
            $shop = User::where('name', $request->shop)->first();

            // Validate the request data
            $validatedData = $request->validate([
                'package' => 'required',
                'length' => 'required',
                'width' => 'required',
                'height' => 'required',
                'weight' => 'required',
            ]);

            // Check if the shop exists
            if ($shop) {
                // Create a new BlockDay record
                $dimension = new Dimension();
                $dimension->user_id = $shop->id;
                $dimension->name = $validatedData['package'];
                $dimension->dimension_unit = $request->dimension_unit;
                $dimension->length = $validatedData['length'];
                $dimension->width = $validatedData['width'];
                $dimension->height = $validatedData['height'];
                $dimension->weight = $validatedData['weight'];
                $dimension->weight_unit = $request->weight_unit;

                // Save the record and return the appropriate response
                if ($dimension->save()) {
                    return response()->json(['status' => true, 'message' => "Box dimension saved successfully"]);
                } else {
                    return response()->json(['status' => false, 'message' => "Failed to save box dimension"]);
                }
            } else {
                return response()->json(['status' => false, 'message' => "Shop not found"]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'errors' => $e->errors()]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => "An unexpected error occurred: " . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $shop = User::where('name', $request->shop)->first();
        if ($shop) {
            $dimension = Dimension::where('user_id', $shop->id)->where('id', $id)->first();
            return response()->json(['status' => true, 'data' => $dimension]);
        } else {
            return response()->json(['status' => false, 'message' => "Shop not found"]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        try {
            // Retrieve the shop based on the shop name from the request
            $shop = User::where('name', $request->shop)->first();

            // Validate the incoming request data
            $validatedData = $request->validate([
                'package' => 'required',
                'length' => 'required',
                'width' => 'required',
                'height' => 'required',
                'weight' => 'required',
            ]);

            // Check if the shop exists
            if ($shop) {
                // Find the block days record by ID
                $dimension = Dimension::find($id);

                if (!$dimension) {
                    return response()->json(['status' => false, 'message' => "Block days record not found"]);
                }

                // $dimension = new PackageDimension();
                $dimension->user_id = $shop->id;
                $dimension->name = $validatedData['package'];
                $dimension->dimension_unit = $request->dimension_unit;
                $dimension->length = $validatedData['length'];
                $dimension->width = $validatedData['width'];
                $dimension->height = $validatedData['height'];
                $dimension->weight = $validatedData['weight'];
                $dimension->weight_unit = $request->weight_unit;
                if ($dimension->save()) {
                    return response()->json(['status' => true, 'message' => "Box dimension update successfully"]);
                } else {
                    return response()->json(['status' => false, 'message' => "Failed to update box dimension"]);
                }
            } else {
                return response()->json(['status' => false, 'message' => "Shop not found"]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'errors' => $e->errors()]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => "An unexpected error occurred: " . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        $shop = User::where('name', $request->shop)->first();
        if ($shop) {
            $package = Dimension::where('user_id', $shop->id)->where('id', $id)->first();
            $package->delete();
            return response()->json(['status' => true, 'message' => "Box dimension delete successfully"]);
        } else {
            return response()->json(['status' => false, 'message' => "Shop not found"]);
        }
    }

    public function updateDimensionSorting ($id, Request $request) {
        $shop = User::where('name', $request->shop)->first();
        if ($shop) {
            $package = Dimension::where('user_id', $shop->id)->where('id', $id)->first();
            $package->sorting = $request->sorting;

            if($package->save()){
                return response()->json(['status' => true, 'message' => "Box dimension update successfully"]);
            }else {
                return response()->json(['status' => false, 'message' => "Failed to update box dimension"]);
            }
           
        } else {
            return response()->json(['status' => false, 'message' => "Shop not found"]);
        }
    }
}
