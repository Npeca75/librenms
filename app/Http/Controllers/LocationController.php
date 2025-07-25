<?php

namespace App\Http\Controllers;

use App\Facades\LibrenmsConfig;
use App\Models\Location;
use Illuminate\Http\Request;
use LibreNMS\Util\Html;

class LocationController extends Controller
{
    public function index()
    {
        $data = [
            'maps_config' => [
                'engine' => LibrenmsConfig::get('geoloc.engine'),
                'api_key' => LibrenmsConfig::get('geoloc.api_key'),
                'tile_url' => LibrenmsConfig::get('leaflet.tile_url', '{s}.tile.openstreetmap.org'),
            ],
            'graph_template' => '',
        ];

        LibrenmsConfig::set('enable_lazy_load', false);
        $graph_array = [
            'type' => 'location_bits',
            'height' => '100',
            'width' => '220',
            'legend' => 'no',
            'id' => '{{id}}',
        ];
        foreach (Html::graphRow($graph_array) as $graph) {
            $data['graph_template'] .= "<div class='col-md-3'>";
            $data['graph_template'] .= str_replace('%7B%7Bid%7D%7D', '{{id}}', $graph); // restore handlebars
            $data['graph_template'] .= '</div>';
        }

        return view('locations', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Location  $location
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, Location $location)
    {
        $this->authorize('admin', $request->user());

        $this->validate($request, [
            'lat' => 'required|numeric|max:90|min:-90',
            'lng' => 'required|numeric|max:180|min:-180',
        ]);

        $location->fill($request->only(['lat', 'lng']));
        $location->fixed_coordinates = true;  // user has set coordinates, block automated changes
        $location->save();

        return response()->json(['status' => 'success']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Location  $location
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception
     */
    public function destroy(Request $request, Location $location)
    {
        $this->authorize('admin', $request->user());

        $location->delete();

        return response()->json(['status' => 'success']);
    }
}
