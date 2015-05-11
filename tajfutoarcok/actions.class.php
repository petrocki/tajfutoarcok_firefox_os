<?php

/**
 * szemely actions.
 *
 * @package    mtfsz_admin
 * @subpackage szemely
 * @author     Petrocki Adam
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class szemelyActions extends sfActions
{
  /**
   * Általános személy szűrő
   * A paraméterként kapott Criteria objektumhoz ad hozzá feltételeket a szűrő űrlapról kapott értékek alapján.
   * Az általános személy tulajdonságokat (név, szervezet, stb.) kezeli.
   * @param Criteria $c A kezelendő Criteria objektum
   * @param array $form_adat A szűrő űrlapról kapott értékek
   */
  private function szemelyListaFilter($c, $form_adat=array()){
  	$con=Propel::getConnection();
  	//szűrés névre
	if(isset($form_adat['nev']) && strlen($form_adat['nev'])>0){
		$c->add(SzemelyPeer::VEZETEKNEV,
			"CONCAT_WS(' ',".SzemelyPeer::VEZETEKNEV.",".SzemelyPeer::KERESZTNEV.") LIKE ".$con->quote($form_adat['nev']."%"), 
				Criteria::CUSTOM
  		);	
	}
	
	//szűrés nemre
	if(!empty($form_adat['nem'])){
		foreach($form_adat['nem'] as $sor){
			$c->addOr(SzemelyPeer::NEM, $sor, Criteria::EQUAL);
		}
	}
	
	//szűrés szul_ev_tol
	if(isset($form_adat['szul_ev_tol']) && strlen($form_adat['szul_ev_tol'])>0){
		$c->addAnd(SzemelyPeer::SZUL_DAT, 'YEAR('.SzemelyPeer::SZUL_DAT.')>='.$con->quote($form_adat['szul_ev_tol']),Criteria::CUSTOM);
	}
	
	//szűrés szul_ev_tol
	if(isset($form_adat['szul_ev_ig']) && strlen($form_adat['szul_ev_ig'])>0){
		$c->addAnd(SzemelyPeer::SZUL_DAT, 'YEAR('.SzemelyPeer::SZUL_DAT.')<='.$con->quote($form_adat['szul_ev_ig']),Criteria::CUSTOM);
	}
	
	//szűrés típusra
	if(!empty($form_adat['tipus'])){
		//******************Feltételek*****************
		//kezedeti, sosem teljesül, ezt bővítjük majd az egyes típusokkal
		$c_ossz=$c->getNewCriterion(SzemelyPeer::SZEMELY_ID,0);
		//******Versenyző
		$c_versenyzo=$c->getNewCriterion(VersenyzoPeer::NYTSZ, NULL, Criteria::ISNOTNULL);
		$c_versenyzo->addAnd($c->getNewCriterion(VersenyzoPeer::IS_DELETED, 0));
		//******Versenybíró
		$c_vbiro=$c->getNewCriterion(VbiroMinositesPeer::MINOSITES, NULL, Criteria::ISNOTNULL);
		
	
		foreach($form_adat['tipus'] as $sor){//multiselect, össze kell VAGY-olni
			if($sor=='VERSENYZO'){//versenyzői típus = van nem törölt rekord a versenyzo táblában
				$c_ossz->addOr($c_versenyzo);
			}
			elseif($sor=='VBIRO'){//versenybírói típus
				$c_ossz->addOr($c_vbiro);
			}
		} 	
		$c->add($c_ossz);
	}
	//szűrés szervezetre
	if(!empty($form_adat['szervezet'])){
		foreach($form_adat['szervezet'] as $sor){
			$c->addOr(SzervezetPeer::SZERVEZET_ID, $sor, Criteria::EQUAL);
		}
	}	
	
  }

  /**
   * Versenyző szűrő
   * A paraméterként kapott Criteria objektumhoz ad hozzá feltételeket a szűrő űrlapról kapott értékek alapján.
   * A versenyzőkre vonatkozó tulajdonságokat (versenyengedély,nytsz, stb.) kezeli.
   * @param Criteria $c A kezelendő Criteria objektum
   * @param array $form_adat A szűrő űrlapról kapott értékek
   */  
  private function versenyzoListaFilter($c, $form_adat=array()){
  	//szűrés dugókaszámra
  	if(isset($form_adat['nytsz']) && $form_adat['si']>0){
  		$c->add(VersenyzoPeer::SI, $form_adat['si']);
  	}
  	
  	//szűrés nyilvántartási számra
	if(isset($form_adat['nytsz']) && strlen($form_adat['nytsz'])>0){
		$c->add(VersenyzoPeer::NYTSZ, trim($form_adat['nytsz']).'%', Criteria::LIKE);
	}
	//szűrés aktivitásra
	if(isset($form_adat['aktivitas'])){
		if($form_adat['aktivitas']==1){
			$c->add(VersenyzoPeer::IS_ACTIVE, 1);
		}
		//versenyengedélyesek
		elseif($form_adat['aktivitas']==2){
			$c->add(VersenyzoPeer::IS_ACTIVE, 1);
			$c->addMultipleJoin(array(
					array(VersenyzoPeer::NYTSZ, VersenyengedelyPeer::NYTSZ), //nytsz alapján kapcsoljuk...
					array(VersenyengedelyPeer::DATUM_TOL, "'".date('Y-m-d')."'", Criteria::LESS_EQUAL),
					array(VersenyengedelyPeer::DATUM_IG, "'".date('Y-m-d')."'", Criteria::GREATER_EQUAL),
			), Criteria::INNER_JOIN);			
		}
	}
	
  } 

  private function versenybiroListaFilter($c, $form_adat=array()){
  	//szűrés minősítésre
	if(!empty($form_adat['vbiro_minosites'])){
		foreach($form_adat['vbiro_minosites'] as $sor){
			$c->addOr(VbiroMinositesPeer::MINOSITES, $sor, Criteria::EQUAL);
		}
	}
  	 
	//szűrés bírói aktivitásra
  	if(!empty($form_adat['vbiro_aktivitas'])){
		foreach($form_adat['vbiro_aktivitas'] as $sor){
			$c->addOr(VbiroMinositesPeer::IS_ACTIVE, $sor, Criteria::EQUAL);
		}
	}
  }
  
  
  public function executeLista(sfWebRequest $request)
  { 	
  	if($request->isMethod('post')){
  		try{
	  		$c=new Criteria();
	  		$json_tomb=array();//json adatokat tartalmazó tömb, amiből a json string készül
	  		
	  		//szemely_szervezet és szervezet tábla kapcsolása
	  		SzemelySzervezetPeer::addJoinToSzemelyDatummal($c, Criteria::LEFT_JOIN);

	  		$c->addMultipleJoin(array(
	  				array(SzemelySzervezetPeer::SZERVEZET_ID, SzervezetPeer::SZERVEZET_ID),
	  				array(SzervezetPeer::IS_DELETED, 0),//ez azért kell, mert ha lejön egy törölt szervezet a rekordba és utólag szűrődik is_deleted=0-ra, az kiszedi az egész rekordot
	  		), Criteria::LEFT_JOIN);
	  		
	  		//versenyző tábla kapcsolása
	  		$c->addMultipleJoin(array(
	  				array(SzemelyPeer::SZEMELY_ID, VersenyzoPeer::SZEMELY_ID),
	  				array(VersenyzoPeer::IS_DELETED, 0),//ez azért kell, mert ha lejön egy törölt versenyző rekord és utólag szűrődik is_deleted=0-ra, az kiszedi az egész rekordot
	  		), Criteria::LEFT_JOIN);
	  		
	  		//vbiro_minosites tábla kapcsolása, versenybíró <=> van nem törölt minősítése a táblában, aminek az intervallumába az aktuális dátum beleesik
	  		$c->addMultipleJoin(array(
	  					array(SzemelyPeer::SZEMELY_ID, VbiroMinositesPeer::SZEMELY_ID), //személy id alapján kapcsoljuk...
	  					array(VbiroMinositesPeer::DATUM_TOL, "'".date('Y-m-d')."'", Criteria::LESS_EQUAL),
	  					array("(".VbiroMinositesPeer::DATUM_IG,  "'".date('Y-m-d')."' OR ".VbiroMinositesPeer::DATUM_IG." IS NULL)", Criteria::GREATER_EQUAL), 
	  					array(VbiroMinositesPeer::IS_DELETED, 0),//ez azért kell, mert ha lejön egy törölt minősítés a rekordba és utólag szűrődik is_deleted=0-ra, az kiszedi az egész rekordot
	  		), Criteria::LEFT_JOIN);//nem lehet szebben, a Join osztály nem kezeli az OR-t
	  		SzemelyPeer::addSelectColumns($c);
	  		$c->addSelectColumn(SzervezetPeer::KOD);
	  		$c->addSelectColumn(VersenyzoPeer::NYTSZ);
	  		$c->addSelectColumn(VersenyzoPeer::SI);
	  		$c->addSelectColumn(VbiroMinositesPeer::MINOSITES);
	  		$c->addAsColumn('vbiro_aktivitas', VbiroMinositesPeer::IS_ACTIVE);
	  		
	  		$params=$request->getParameter('szemelyParams');
	  		
	  		if($params['szervezet_id']>0){
	  			$c->add(SzemelySzervezetPeer::SZERVEZET_ID, $params['szervezet_id']);//csak a jelenelgi egyesületi tagokat listázzuk
	  		}

	  		if($params['tipus']=='versenyzo'){
	  			//ha csak a versenyzők kellenek
	  			$c->addOr(VersenyzoPeer::NYTSZ, NULL, Criteria::NOT_EQUAL);
				$c->addAnd(VersenyzoPeer::IS_DELETED, 0);
				
	  		}
	  		elseif($params['tipus']=='versenybiro'){
	  			//ha csak a versenybírók kellenek
	  			$c->add(VbiroMinositesPeer::VBIRO_MINOSITES_ID, NULL, Criteria::ISNOTNULL);
	  		}else{
	  			//ha sima személy lista kell (a típus itt is szűrhető, de az a szemelyListaFilter-ben megy)
	  		
	  		}
	  		
  			if($params['tipus']=='elhunytak'){
	  			$c->add(SzemelyPeer::HALALOZAS_DAT, NULL, Criteria::ISNOTNULL);
	  		}else{
	  			$c->add(SzemelyPeer::HALALOZAS_DAT, NULL, Criteria::ISNULL);//TODO halálozás feltétel az összes action-be!!!
	  		}
	  		
	  		$filterName="szemelyListaFilter";//szűrő űrlap küldött paraméterének neve
	  		
	  		$filterFormAdat=$request->getParameter($filterName);//elküldött form adatai (üres, ha nincs elküldve)
	  		$this->szemelyListaFilter($c,$filterFormAdat);
	  		
	  		if($params['tipus']=="versenyzo"){//versenyző szűrők hozzáadása
	  			$this->versenyzoListaFilter($c,$filterFormAdat);
	  		}elseif($params['tipus']=="versenybiro"){
	  			$this->versenybiroListaFilter($c,$filterFormAdat);
	  		}
	  		
			myTools::addListSorting($c, $request->getParameter('jtSorting'));//rendezési feltételek hozzáadása $c-hez
			$json_tomb["TotalRecordCount"]=SzemelyPeer::doCount($c);
			$json_tomb["Criteria"]=base64_encode(serialize($c));//visszadjuk a criteria-t a json tömbben, az exportáláshoz kell majd
			
			myTools::addListPaging($c, $request->getParameter('jtStartIndex'), $request->getParameter('jtPageSize'));
			$items=SzemelyPeer::doSelectStmt($c)->fetchAll(PropelPDO::FETCH_ASSOC);//ez PDOResultset-et ad vissza, nem Szemely tömböt, mint a doSelect

			$json_tomb["Result"]="OK";
			$json_tomb["Records"]=array();
			
			for($i=0;$i<count($items);$i++){
				$is_public=2;
				
				if($this->getUser()->isAuthenticated()){
					$szul_dat=$items[$i]['SZUL_DAT'];
					$nytsz=$items[$i]['NYTSZ'];
					$is_public=1;
				}else{
					$szul_dat=date('Y', strtotime($items[$i]['SZUL_DAT']));
					$nytsz=substr_replace($items[$i]['NYTSZ'], '****', 3, 4);
				}
				
				$fajl=FajlPeer::retrieveByPK($items[$i]['PROFILKEP_ID']);
				
				if($fajl!=null && $fajl->getIsPublic()>=$is_public){
					$profilkep=$fajl->getFajlId();
				}else{
					$profilkep=$items[$i]['NEM'];//1 és 2 id-jű képek a default profilképek
				}
				
				$vbiro_minosites=KodszotarPeer::getFromKodszotar('VBIRO_MIN',$items[$i]['MINOSITES']);
				
				$json_tomb["Records"][]=array(
						"profilkep" => $profilkep,
						"SzemelyPeer__SZEMELY_ID" => $items[$i]['SZEMELY_ID'],
						"SzemelyPeer__VEZETEKNEV" => $items[$i]['VEZETEKNEV'],
						"SzemelyPeer__KERESZTNEV" => $items[$i]['KERESZTNEV'],
						"SzemelyPeer__SZUL_DAT" => $szul_dat,
						"SzervezetPeer__KOD" => (strlen($items[$i]['KOD'])>0?$items[$i]['KOD']:"-"),
						"VersenyzoPeer__NYTSZ" => (strlen($items[$i]['NYTSZ'])>0?$nytsz:"-"),
						"VersenyzoPeer__SI" => (strlen($items[$i]['SI'])>0?$items[$i]['SI']:"-"),
						"VbiroMinositesPeer__MINOSITES" => (is_object($vbiro_minosites)?$vbiro_minosites->getMegnevezes():"-"),
						"VbiroMinositesPeer__IS_ACTIVE" => (is_object($vbiro_minosites)?myTools::displayBoolean($items[$i]['vbiro_aktivitas']):"-"),
						);
			}
  		}catch(Exception $e){
			$json_tomb["Result"]="ERROR";
			$json_tomb["Message"]=$e->getMessage();
		}
		return $this->renderText(json_encode($json_tomb));
		
	}else{//ha nem post kérés, akkor betöltjük az űrlapot
		$this->szervezet_id=($request->getParameter('szervezet_id')>0?$request->getParameter('szervezet_id'):0);
		$this->tipus=(strlen($request->getParameter('tipus'))>0?$request->getParameter('tipus'):"szemely");
		$this->filterForm=new SzemelyListaFilterForm();
		if($this->szervezet_id>0){
			$this->forward404Unless($this->szervezet=SzervezetPeer::retrieveByPK($this->szervezet_id));
		}else{
			$this->szervezet=null;
		}
		$c=new Criteria();
		$c->add(SzervezetPeer::TIPUS, 'EGYESULET');
		$c->addJoin(SzervezetPeer::SZERVEZET_ID, TagszervezetPeer::SZERVEZET_ID, Criteria::LEFT_JOIN);
		$c->addAscendingOrderByColumn(TagszervezetPeer::SZERVEZET_ID.' IS NULL');//tagszervezetek előre
		$this->szervezetek=SzervezetPeer::getSzervezetsWithoutJogutod($c);
	}	
  }
  
  public function executeShow(sfWebRequest $request)
  {
  	$szemely_id=$request->getParameter('szemely_id');
  	$this->forward404Unless($this->szemely=SzemelyPeer::retrieveByPK($szemely_id));
  	$c=new Criteria();
  	$c->addDescendingOrderByColumn(SzemelySzervezetPeer::DATUM_TOL);
  	$this->tagsagok=$this->szemely->getSzemelySzervezets($c);
  }
  
  public function executeKepLista(sfWebRequest $request)
  {
  	$szemely_id=$request->getParameter('szemely_id');
  	$this->forward404Unless($this->szemely=SzemelyPeer::retrieveByPK($szemely_id));
  	$c=new Criteria();
  	if ($this->getUser()->isAuthenticated()) {
  		$c->add(FajlPeer::IS_PUBLIC, array(1,2), Criteria::IN);
  	} else {
  		$c->add(FajlPeer::IS_PUBLIC, 2);
  	}
  	$c->add(SzemelyKepPeer::IS_CROPPED,1);
  	$this->kepek=$this->szemely->getSzemelyKepsJoinFajl($c);
  }
  
  public function executeShowVersenybiro(sfWebRequest $request)
  {  
	  $szemely_id=$request->getParameter('szemely_id');
	  	$this->forward404Unless($this->szemely=SzemelyPeer::retrieveByPk($szemely_id));
	  	$c=new Criteria();
	  	$c->addDescendingOrderByColumn(VbiroMinositesPeer::DATUM_TOL);
	  	$this->vbiro_minositesek=$this->szemely->getVbiroMinositess($c);

	  //a bejegyzés listához
	  $this->param="szemely_id=".$szemely_id;
  }


  public function executeVersenybiroBejegyzesLista(sfWebRequest $request)
  {
	$szemely_id=$request->getParameter('szemely_id');
	try{
		$c=new Criteria();
		if($szemely_id>0){
			$c->add(VbiroBejegyzesPeer::SZEMELY_ID, $szemely_id);
		}else{
			throw new Exception('A bejegyzések tulajdonosa nem található!');
		}

		$json_tomb=array();//json adatokat tartalmazó tömb, amiből a json string készül

		myTools::addListSorting($c, $request->getParameter('jtSorting'));//rendezési feltételek hozzáadása $c-hez
		myTools::addListPaging($c, $request->getParameter('jtStartIndex'), $request->getParameter('jtPageSize'));
		$bejegyzes=VbiroBejegyzesPeer::doSelect($c);

		$json_tomb["Result"]="OK";
		$json_tomb["TotalRecordCount"]=count($bejegyzes);
		$json_tomb["Records"]=array();

		for($i=0;$i<count($bejegyzes);$i++){
			$bejegyzo=$bejegyzes[$i]->getSzemelyRelatedByBejegyzo();
			$json_tomb["Records"][]=array(
					"vbiro_bejegyzes_id" => $bejegyzes[$i]->getVbiroBejegyzesId(),
					"VbiroBejegyzesPeer__DATUM" => $bejegyzes[$i]->getDatum(),
					"VbiroBejegyzesPeer__TIPUS" => $bejegyzes[$i]->getTipusKiir(),
					"VbiroBejegyzesPeer__ERTEK" => $bejegyzes[$i]->getErtek(),
					"VbiroBejegyzesPeer__MEGNEVEZES" => $bejegyzes[$i]->getMegnevezes(),
					"VbiroBejegyzesPeer__BEJEGYZO" => ($bejegyzo!=NULL?$bejegyzo->getNev():''),
					"VbiroBejegyzesPeer__MEGJEGYZES" => $bejegyzes[$i]->getMegjegyzes(),
			);
		}
		//$this->result=json_encode($json_tomb);//enélkül elszáll a try-catch blokk, de miért????
	}catch(Exception $e){
		$json_tomb["Result"]="ERROR";
		$json_tomb["Message"]=$e->getMessage();
	}
	return $this->renderText(json_encode($json_tomb));

  }
  
  public function executeTajfutoArcokKvizGenerator(sfWebRequest $request)
  {
  	$c=new Criteria();
  	$c->addJoin(FajlPeer::FAJL_ID, SzemelyKepPeer::FAJL_ID);
  	$c->add(FajlPeer::IS_PUBLIC,2);
  	$c->add(SzemelyKepPeer::IS_CROPPED,1);
  	$kepek=SzemelyKepPeer::doSelect($c);
  	
  	//kisorsoljuk a képet
  	$rand=rand(0,count($kepek)-1);
  	$kep=$kepek[$rand];
  	$megoldasok=array();
  	//megoldások sorrendje
  	$helyes=rand(0,2);
  	$masodik=(2-$helyes!=$helyes?2-$helyes:2-$helyes+1);
  	$harmadik=3-$helyes-$masodik;
  	
  	$megoldasok[$helyes]=$kep->getSzemely()->getNev();
  	//másik 2 lehetőség kisorsolása, azonos nem, +/-10 év életkor
	$c=new Criteria();
	$c->add(SzemelyPeer::NEM, $kep->getSzemely()->getNem());
  	$szemelyek=SzemelyPeer::doSelect($c);
  	$rand=rand(0,count($szemelyek)-1);
  	$megoldasok[$masodik]=$szemelyek[$rand]->getNev();
  	do{
  		$rand_uj=rand(0,count($szemelyek)-1);
  		$megoldasok[$harmadik]=$szemelyek[$rand_uj]->getNev();
  	}while($rand==$rand_uj);
  	
  	
  	$json_tomb=array();
  	$json_tomb["fajl_id"]=$kep->getFajlId();
  	$json_tomb["megoldasok"]=$megoldasok;
  	$json_tomb["helyes"]=$helyes;
  	
  	$this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');//más hosztról is elérhető legyen ajax-szal
  	return $this->renderText(json_encode($json_tomb));
  }
}