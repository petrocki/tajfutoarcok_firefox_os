//üdvözlő zenet vagy belépés gomb megjelenítése
function displayUserItems(){
	if(localStorage.getItem("szemely_id")>0){
		$("#udvozlet").show();
		$("#nev").html(localStorage.getItem("nev"));
		$("#login-btn").hide();
		$("#upload-btn").prop("disabled", false);
	}else{
		$("#udvozlet").hide();
		$("#login-btn").show();
		$("#upload-btn").prop("disabled", true);
	}
}
displayUserItems();

$("#upload-btn").on("click", function(){
	if($("#upload-btn").prop("disabled")==false){
		window.location="upload.html";
	}	
});

$("#logout-btn").on("click", function(){
	logout();	
});