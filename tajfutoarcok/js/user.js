function login(){
	localStorage.setItem("szemely_id",1);
	localStorage.setItem("nev","Teszt Felhasználó");
	localStorage.setItem("accessToken", "jhJHDSAJNjdajda321k@&vx");
	displayUserItems();
}

function logout(){
	localStorage.removeItem("szemely_id");
	localStorage.removeItem("nev");
	localStorage.removeItem("accessToken");
	displayUserItems();
}