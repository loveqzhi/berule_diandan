$(function() {
    var QQmap = {
        map : null,

        marker: null,

        moveListen: null,

        init : function(lat, lng) {
            var center = new qq.maps.LatLng(lat, lng);
            this.map = new qq.maps.Map(document.getElementById('container'),{
                center: center,
                zoom: 15
            });
            this.marker = new qq.maps.Marker({
                position: center,
                draggable: true,
                map: QQmap.map
            });
        },

        latlngBounds: new qq.maps.LatLngBounds(),

        searchService: new qq.maps.SearchService({
                location: "上海",
                pageIndex: 1,
                pageCapacity: 1,
                autoExtend: true,
                complete: function(results) {
                    var pois = results.detail.pois;
                    var poi = pois[0];

                    QQmap.latlngBounds.extend(poi.latLng);

                    qqmap_dragend(poi.latLng.lat, poi.latLng.lng);

                    QQmap.marker = new qq.maps.Marker({
                        map: QQmap.map,
                        draggable: true,
                        position: poi.latLng
                    });

                    QQmap.map.fitBounds(QQmap.latlngBounds);

                    QQmap.moveMarker();
                },
                error: function() {
                    alert("出错了。");
                }
            }),

        clearOverlays: function(overlay) {
            overlay.setMap(null);
        },

        moveMarker: function() {
            if (QQmap.moveListen) {
                qq.maps.event.removeListener(QQmap.moveListen);
                QQmap.moveListen = null;
            };
            QQmap.moveListen = qq.maps.event.addDomListener(QQmap.marker, 'dragend', function(e){
                qqmap_dragend(e.latLng.lat.toFixed(6), e.latLng.lng.toFixed(6));
            });
        }
    }


    var lat = $("#lat").val(),
        lng = $("#lng").val();

    QQmap.init(lat, lng);
    QQmap.moveMarker();

    $("#mapaddr").keydown(function(e) {
        if (e.keyCode == 13) {
            qqmapSearch();
        };
    });

    $("#qqmap-search-btn").click(function() {
        qqmapSearch();
    });

    function qqmap_dragend(lat, lng){
        $('#lat').val(lat);
        $('#lng').val(lng);
    };

    function qqmapSearch() {
        var keyword = $("#mapaddr").val();
        if (keyword) {
            QQmap.clearOverlays(QQmap.marker);
            QQmap.searchService.search(keyword);
        };
    };
})

function qqmap_toggle() {
    if($('#divmap').css('left')=='59px') {
        $('#divmap').css('left','-9999px'); 
    } else {
        $('#divmap').css('left','59px'); 
    }
}