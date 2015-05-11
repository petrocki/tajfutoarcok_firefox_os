var JSONres;
function kviz(){
	$.ajax({
	  method: "GET",
	  url: "http://adatbank.mtfsz.hu/index.php/szemely/tajfutoArcokKvizGenerator"
	})
	  .done(function( result ) {
	    JSONres=JSON.parse(result);
	    var img = $("<img />").attr('src', 'http://admin.mtfsz.hu/fajl/get/fajl_id/'+JSONres.fajl_id+'/tipus/render')
	    .load(function() {
	        if (!this.complete || typeof this.naturalWidth == "undefined" || this.naturalWidth == 0) {
	            alert('broken image!');
	        } else {
	            $("#img-div").html(img);
	        }
	    });
	    $("#choices").html("");
		$.each(JSONres.megoldasok,function(index, value){
		    $("#choices").append('<input type="radio" name="megoldas" value="'+index+'" id="m'+index+'" />'+value+'<br/>');
		});
	}).fail(function() {
	    alert( "Az adatok lekérdezése a szerverről sikertelen!");
	    document.location="index.html";
	});
	
	//mentés elrejtése vagy megjelenítése
	if(localStorage.osszes<5){
		$("#save").hide();
	}else{
		$("#save").show();
	}
}
kviz();

localStorage.setItem("helyes",0);
localStorage.setItem("osszes",0);

function next(){
	localStorage.osszes++;
	if($('#m'+JSONres.helyes).is(':checked')){
		localStorage.helyes++;
		alert("Jó válasz! Eredmény "+localStorage.helyes+"/"+localStorage.osszes);
	}else{
		alert("Rossz válasz! Eredmény "+localStorage.helyes+"/"+localStorage.osszes);
	}
	kviz();
}

$("#next-btn").on("click", function(){
	next();	
});