var QueryString = function () {
	// This function is anonymous, is executed immediately and 
	// the return value is assigned to QueryString!
	var query_string = {};
	var query = window.location.search.substring(1);
	var vars = query.split("&");
	for (var i=0;i<vars.length;i++) {
		var pair = vars[i].split("=");
		// If first entry with this name
		if (typeof query_string[pair[0]] === "undefined") {
			query_string[pair[0]] = pair[1];
			// If second entry with this name
		}
		else if (typeof query_string[pair[0]] === "string") {
			var arr = [ query_string[pair[0]], pair[1] ];
			query_string[pair[0]] = arr;
			// If third or later entry with this name
		}
		else {
			query_string[pair[0]].push(pair[1]);
		}
	} 
	return query_string;
} ();

var adm_latlng = decodeURIComponent(QueryString.latlng).replace(' ', '');
var adm_lat = +adm_latlng.split(',')[0];
var adm_lng = +adm_latlng.split(',')[1];

var gmap_link = 'http://maps.google.com.tw/maps?q=' + adm_lat + ',' + adm_lng;
$('#show_me_gmap').html("<a target='_blank' href='"+gmap_link+"'>GMap有圖有真相"+ adm_lat + ',' + adm_lng+"</a>");

var mapZoom;
if (QueryString.zoom !== undefined) {
	mapZoom = +QueryString.zoom;
}
else {
	mapZoom = 11;
}

var map, svg, g;
map = new L.Map('map').setView(new L.LatLng(adm_lat, adm_lng), mapZoom).addLayer(new L.TileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'));

var iconx = 42;
var icony = 51;
var anchorx = 25;
var fun_anchorx = anchorx;
var anchory = 51;
var fun_anchory = anchory;
var myIcon = L.icon({
	iconUrl: 'rkcatyo.png',
	iconSize: [iconx, icony],
	iconAnchor: [anchorx, anchory],
});
var marker_lat = adm_lat;
var marker_lng = adm_lng;


var rkmarker = L.marker([adm_lat, adm_lng], {icon: myIcon}).addTo(map);
var jumpIcon = L.icon({
	iconUrl: 'rkcatyo.gif',
	//iconUrl: 'http://yll.loxa.edu.tw/gificon/animal/bear/bear_0036.gif',
	//iconUrl: 'http://yll.loxa.edu.tw/gificon/animal/bear/bear_0001.gif',
	iconSize: [42, 51],
	iconAnchor: [25, 61],
});




var search;
var gres = [];
var gmarker = L.marker();
var gmarkers = [];


function b2cat () {
	olat = adm_lat;
	olng = adm_lng;
	map.fitBounds(fit_grid);
	map.panTo([adm_lat, adm_lng]);
	rkmarker.setLatLng([olat, olng]);
	rkmarker.setIcon(myIcon);
}


function panToAddr(gres_id) {
//	gplusmarker = L.marker(gres[gres_id].location).addTo(map);
	gres_id = +gres_id;
	map.panTo(gres[gres_id].location);
	map.fitBounds(gres[gres_id].bounds);
}

function showCoords (latlng) {
//	d3.select("#gcoords").attr('value', latlng);
	gcoords.setAttribute('value', latlng);
	gcoords.select();
	gcoords.focus();
}

function osmaddrlookup() {
	var osmep = "http://nominatim.openstreetmap.org/reverse";
	var odata = {
		lat:adm_lat, 
		lon: adm_lng, 
		format: 'json', 
		'_': $.now(),
		osm_type: 'W',
		zoom: 18,
		addressdetails: 1
	};
	$.ajax({
		url: osmep,
		data: odata,
		success: function(data){
			osm_result.value = data.display_name.replace('臺','台').replace('臺', '台');
		},
		cache: true,
		datatype: 'json'
	});
	/*
	var jqxhr = $.get(osmep, odata, function (data) {
		osm_result.value = data.display_name;
	},'json');
	//*/
}
osmaddrlookup();

function gaddrlookup() {
	var gep = "http://maps.googleapis.com/maps/api/geocode/json";
	var gdata = {
		latlng:adm_lat + "," + adm_lng,
		sensor: false
	};
	$.ajax({
		url: gep,
		data: gdata,
		success: function(data){
			g_result.value = data.results[0].formatted_address.replace('臺','台');
			data.results.forEach(function(d) {
				if (d.types.indexOf('route') != -1) {
					g_result.value = d.formatted_address.replace('臺','台');
				}
			});
		},
		cache: true,
		datatype: 'json'
	});
	/*
	var jqxhr = $.get(osmep, odata, function (data) {
		osm_result.value = data.display_name;
	},'json');
	//*/
}
gaddrlookup();






function gAddress (addr) {
	gres = [];
	var gurl = "http://maps.googleapis.com/maps/api/geocode/json";
	var gdata = {address : addr};
	var html;

	var sw = [];
	var ne = [];

//	map.removeLayer(gmarker)
	gmarkers.forEach(function (m) {
		map.removeLayer(gmarker);
	});

	$.ajax({
		url: gurl, 
		data: gdata,
		success: function (data) {
			html = "<ul>";
			data.results.forEach(function(d){
				var gres_id = gres.length;
				var b;
				if (d.geometry.bounds !== undefined) {
					b = d.geometry.bounds;
				}
				else {
					b = d.geometry.viewport;
				}
				sw = [b.southwest.lat, b.southwest.lng];
				ne = [b.northeast.lat, b.northeast.lng];
				var gres_latlng = [d.geometry.location.lat, d.geometry.location.lng];
				gres.push({
					"formatted_address": d.formatted_address,
					"location": gres_latlng,
					"bounds": [sw, ne]
				});
				html += "<li><div class='gres_list' onclick='panToAddr("+gres_id+", true)' onmouseover='showCoords("+'"'+d.geometry.location.lat+','+d.geometry.location.lng+'"'+")'>"+d.formatted_address+"</div></li>";

				gmarkers.push(L.marker(gres_latlng));
				gmarkers[gmarkers.length-1].bindPopup("<label>" + d.formatted_address + "</label><div>" + d.geometry.location.lat + ',' + d.geometry.location.lng + "</div>");
				gmarkers[gmarkers.length-1].addTo(map);

			});
			html += "</ul>";
			$('#gresult').html(html);
		},
		dataType: 'json',
		cache: true
	});

	var newUrl = window.location.origin + window.location.pathname + '?latlng=' + adm_lat + ',' + adm_lng + '&zoom=' + mapZoom + '&fit=no' + '&search=' + addr;
	search = addr;
	window.history.pushState(adm_lat + ',' + adm_lng, 'latlng', newUrl);
}


function floorDec (num) {
	num = Math.floor(num * 1000);
	diff = num % 5;
	num -= diff;
	return num / 1000;
}

function showPopover (d) {
	d3.select("text."+"rid-"+d.properties.rid).attr('class', 'vill-label-show ' + 'rid-' + d.properties.rid);
	d3.select("#placename").attr('value', d.properties.name.replace('臺', '台').replace('臺', '台'));
	placename.select();
	placename.focus();
}

function removePopovers(d) {
	d3.select("text."+"rid-"+d.properties.rid).attr('class', 'vill-label-hide ' + 'rid-' + d.properties.rid);
}

if (!!QueryString.search) {
	search = decodeURIComponent(QueryString.search);
	gAddress (search);
	gaddress.value = search;
}
else {
	search = "";
}

var popup;
map.on('contextmenu',function(e) {
	var newUrl = window.location.origin + window.location.pathname + '?latlng=' + e.latlng.lat + ',' + e.latlng.lng + '&zoom=' + mapZoom + '&fit=no' + '&search=' + search;
	popup = L.popup().setLatLng([e.latlng.lat, e.latlng.lng]).setContent("<a href='"+newUrl+"'>移到這點<br/>"+e.latlng.lat+","+e.latlng.lng+"</a>"); 
	popup.openOn(map);
});


var drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);

map.on('layeradd', function (e) {
	drawnItems.addLayer(e.layer);
});


map.on('moveend', function(e){
	var ffss = 5566;
});


/*
var circle = L.circle([adm_lat, adm_lng], 1000, {
	color: 'red',
	fillColor: '#f03',
	fillOpacity: 0.5
}).addTo(map);
//*/

//*
var fitSize = 0.02;
var adm_grid = [[floorDec(adm_lat)-0.005, floorDec(adm_lng)-0.005], [floorDec(adm_lat) + 0.01, floorDec(adm_lng) + 0.01]];
var fit_grid = [[floorDec(adm_lat)-fitSize, floorDec(adm_lng)-fitSize], [floorDec(adm_lat) + fitSize + 0.005, floorDec(adm_lng) + fitSize + 0.005]];
//var adm_grid = [[54.559322, -5.767822], [56.1210604, -3.021240]];
var rect = L.rectangle(adm_grid, {
//	color: 'black',
	fillColor: '#ff0',
	fillOpacity: .5,
	weight: 1
}).addTo(map);

var adm_grid_core = [[floorDec(adm_lat), floorDec(adm_lng)], [floorDec(adm_lat) + 0.005, floorDec(adm_lng) + 0.005]];
//var adm_grid = [[54.559322, -5.767822], [56.1210604, -3.021240]];
var rect_core = L.rectangle(adm_grid_core, {
//	color: 'black',
	fillColor: '#f00',
	fillOpacity: .3,
	weight: 1
}).addTo(map);

var fun_lat, fun_lng, olat=adm_lat, olng=adm_lng;
var clicktimes = 0;
rkmarker.on('click', function (e) {
	clicktimes++;
	var mx = e.originalEvent.x;
	var my = e.originalEvent.y;
	var ox = map.latLngToContainerPoint(e.latlng).x;
	var oy = map.latLngToContainerPoint(e.latlng).y - $(window).scrollTop();
	var lx = ox - anchorx;
	var rx = ox + (iconx - anchorx);
	var ty = oy - anchory;
	var by = oy + (icony - anchory);
	var cx = (rx + lx) / 2;
	var cy = (ty + by) / 2;

	var dx = mx - cx;
	var dy = my - cy;

	fun_anchorx += dx;
	fun_anchory += dy;


	ox -= dx;
	oy -= dy;


	// 左上角
	var fun_latlng = map.layerPointToLatLng([ox-fun_anchorx, oy-fun_anchory+$(window).scrollTop()]);
	fun_lat = fun_latlng.lat;
	fun_lng = fun_latlng.lng;

	// 原點
	var olatlng = map.containerPointToLatLng([ox, oy+$(window).scrollTop()]);
	olat = olatlng.lat;
	olng = olatlng.lng;

	//var mm = L.marker([fun_lat, fun_lng]).bindPopup("左上唷<br/>"+fun_lat+","+fun_lng);
	//var mm = L.marker([olat, olng]).bindPopup("原點唷<br/>"+olat+","+olng);
	//mm.addTo(map);

	var jumpIcon = L.icon({
		iconUrl: 'rkcatyo_2.gif',
		//iconUrl: 'http://yll.loxa.edu.tw/gificon/animal/bear/bear_0036.gif',
		//iconUrl: 'http://yll.loxa.edu.tw/gificon/animal/bear/bear_0001.gif',
		iconSize: [iconx, icony],
		//iconAnchor: [fun_anchorx, fun_anchory],
		iconAnchor: [anchorx, anchory],
	});

	var angryIcon = L.icon({
		iconUrl: 'rkcatyo_angry.gif',
		//iconUrl: 'http://yll.loxa.edu.tw/gificon/animal/bear/bear_0036.gif',
		//iconUrl: 'http://yll.loxa.edu.tw/gificon/animal/bear/bear_0001.gif',
		iconSize: [iconx, icony],
		//iconAnchor: [fun_anchorx, fun_anchory],
		iconAnchor: [anchorx, anchory],
	});
	e.target.setLatLng([olat, olng]);
	if (clicktimes <= 10) {
		e.target.setIcon(jumpIcon);
	}
	else {
		e.target.setIcon(angryIcon);
	}
});




if (QueryString.fit == 'no') {
}
else {
	map.fitBounds(fit_grid);
}
//*/



svg = d3.select(map.getPanes().overlayPane).append('svg');
g = svg.append('g');

var getAndDrawGeoJSON = function () {
d3.json('http://taibif.tw/vgd/aprxGeoValidation/getGeoJSON.php?debug=&latlng='+adm_latlng, function(collection){
	if (collection.features.length == 0) return;
	var reset, project, bounds, path, feature, villLabel;
	reset = function(){
		var bottomLeft, topRight;
		bottomLeft = project(bounds[0]);
		topRight = project(bounds[1]);
		svg.attr('width', topRight[0] - bottomLeft[0]).attr('height', bottomLeft[1] - topRight[1]).style('margin-left', bottomLeft[0] + 'px').style('margin-top', topRight[1] + 'px');
		g.attr('transform', 'translate(' + -bottomLeft[0] + ',' + -topRight[1] + ')');
		villLabel.attr("transform", function(d) {
			return "translate(" + path.centroid(d) + ")";
		});
		feature.attr('d', path);

		mapZoom = map.getZoom();
		//window.history.pushState(e.latlng.lat + ',' + e.latlng.lng, 'latlng', "/" + mapZoom);
		var newUrl = window.location.origin + window.location.pathname + '?latlng=' + adm_lat + ',' + adm_lng + '&zoom=' + mapZoom + '&fit=no' + '&search=' + search;
		window.history.pushState(adm_lat + ',' + adm_lng, 'latlng', newUrl);

		console.log([olat, olng]);
		rkmarker.setLatLng([olat, olng]);

		return feature;
	};
	project = function(x){
		var point;
		point = map.latLngToLayerPoint(new L.LatLng(x[1], x[0]));
		return [point.x, point.y];
	};
	bounds = d3.geo.bounds(collection);
	path = d3.geo.path().projection(project);

	villLabel = g.selectAll('text').data(collection.features).enter().append('text')
			.attr("transform", function(d) {
				return "translate(" + path.centroid(d) + ")";
			})
			.text(function(d) {
				return d.properties.name;
			})
			.attr('class', function(d) { return 'vill-label-hide ' + 'rid-' + d.properties.rid;});

	feature = g.selectAll('path').data(collection.features).enter().append('path')
			.on('mouseover', function (d) {showPopover.call(this, d);})
			.on("mouseout", function (d) { removePopovers(d); })
			.attr('class', function(d) { return 'vill-region ' + 'rid-' + d.properties.rid;});

	map.on('viewreset', function(){
		console.log('reseting');
		return reset();
	});

	return reset();
})} ();
