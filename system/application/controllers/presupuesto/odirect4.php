<?php
class odirect4 extends Controller {

	var $titp='Rendici&oacute;n de Cuentas';
	var $tits='Rendici&oacute;n de Cuenta';
	var $url='presupuesto/odirect4/';

	function odirect4(){
		parent::Controller();
		$this->load->library("rapyd");
		$this->formatopres =$this->datasis->traevalor('FORMATOPRES');
		$this->flongpres   =strlen(trim($this->formatopres));
	}

	function index(){
		redirect($this->url."filteredgrid");
	}

	function filteredgrid(){
		//$this->datasis->modulo_id(101,1);
		$this->rapyd->load("datafilter","datagrid");

		$mSPRV=array(
				'tabla'   =>'sprv',
				'columnas'=>array(
				'proveed' =>'C&oacute;odigo',
				'nombre'=>'Nombre',
				'contacto'=>'Contacto'),
				'filtro'  =>array('proveed'=>'C&oacute;digo','nombre'=>'Nombre'),
				'retornar'=>array('proveed'=>'cod_prov' ),
				'titulo'  =>'Buscar Beneficiario');

		$bSPRV=$this->datasis->p_modbus($mSPRV,"proveed");

		$filter = new DataFilter("Filtro de $this->titp","odirect");

		$filter->tipo = new dropdownField("Orden de ", "tipo");
		$filter->tipo->option("","");
		$filter->tipo->option("Compra"  ,"Compra");
		$filter->tipo->option("Servicio","Servicio");
		$filter->tipo->style="width:100px;";

		$filter->fecha = new dateonlyField("Fecha", "fecha");
		$filter->fecha->size=12;

		$filter->uejecutora = new inputField("Unidad Ejecutora", "uejecutora");
		$filter->uejecutora->size=12;

		$filter->cod_prov = new inputField("Beneficiario", 'cod_prov');
		$filter->cod_prov->size = 6;
		$filter->cod_prov->append($bSPRV);
		$filter->cod_prov->rule = "required";

		$filter->beneficiario = new inputField("Beneficiario", "beneficiario");
		$filter->beneficiario->size=60;

		$filter->buttons("reset","search");


		$filter->build();
		$uri = anchor($this->url.'dataedit/show/<#numero#>','<str_pad><#numero#>|8|0|STR_PAD_LEFT</str_pad>');

		$grid = new DataGrid("Lista de ".$this->titp);
		$grid->order_by("numero","desc");
		$grid->per_page = 20;
		$grid->use_function('substr','str_pad');

		$grid->column("N&uacute;mero"    ,$uri);
		$grid->column("Tipo"             ,"tipo"                                        ,"align='center'");
		$grid->column("Fecha"            ,"<dbdate_to_human><#fecha#></dbdate_to_human>","align='center'");
		$grid->column("Unidad Ejecutora" ,"uejecutora");
		$grid->column("Beneficiario"        ,"cod_prov");
		$grid->column("Beneficiario"     ,"beneficiario");
		$grid->column("Pago"             ,"<number_format><#pago#>|2|,|.</number_format>","align='rigth'");
	//	$grid->column("Devoluci&oacute;n","<number_format><#devo#>|2|,|.</number_format>","align='rigth'");
		//echo $grid->db->last_query();
		$grid->add($this->url."dataedit/create");
		$grid->build();

		$data['content'] = $filter->output.$grid->output;
		$data['title']   = " $this->titp ";
		$data["head"]    = $this->rapyd->get_head();
		$this->load->view('view_ventanas', $data);
	}

	function dataedit(){
		//$this->datasis->modulo_id(101,1);
		$this->rapyd->load('dataobject','datadetails');

		$mSPRV=array(
				'tabla'   =>'sprv',
				'columnas'=>array(
				'proveed' =>'C&oacute;odigo',
				'nombre'=>'Nombre',
				'contacto'=>'Contacto'),
				'filtro'  =>array('proveed'=>'C&oacute;digo','nombre'=>'Nombre'),
				'retornar'=>array('proveed'=>'cod_prov', 'nombre'=>'nombre','reteiva'=>'reteiva_prov' ),
				'titulo'  =>'Buscar Beneficiario');

		$bSPRV=$this->datasis->p_modbus($mSPRV,"proveed");

		$modbus=array(
			'tabla'   =>'ppla',
			'columnas'=>array(
				'codigo'      =>'C&oacute;digo',
				'denominacion'=>'Denominaci&oacute;n'),
			'filtro'  =>array('codigo' =>'C&oacute;digo','denominacion'=>'Denominaci&oacute;n'),
			'retornar'=>array('codigo'=>'partida_<#i#>'),
			'p_uri'=>array(4=>'<#i#>',5=>'<#fondo#>',6=>'<#estadmin#>',),
			'where'=>'tipo=<#fondo#> AND codigoadm=<#estadmin#> AND LENGTH(ppla.codigo)='.$this->flongpres,
			'join' =>array('presupuesto','presupuesto.codigopres=ppla.codigo',''),
			'titulo'  =>'Busqueda de partidas');
		$btn=$this->datasis->p_modbus($modbus,'<#i#>/<#fondo#>/<#estadmin#>');

		$mMOVI=array(
			'tabla'   =>'movi',
			'columnas'=>array(
				'numero'       =>'Nu&acute;mero',
				'codbanc'     =>'Banco',
				'monto'        =>'Monto',
				'saldo'        =>'Demostrado'),
			'filtro'  =>array(
				'numero'=>'Nu&acute;mero',
				'cod_banc'=>'Banco'),
			'retornar'=>array(
				'numero'=>'movi'),
			'titulo'  =>'Buscar Anticipo');

		$pMOVI=$this->datasis->p_modbus($mMOVI,'movi');

		$do = new DataObject("odirect");

		$do->rel_one_to_many('itodirect', 'itodirect', array('numero'=>'numero'));

		$edit = new DataDetails($this->tits, $do);
		$edit->back_url = site_url($this->url."/filteredgrid");
		$edit->set_rel_title('itodirect','Rubro <#o#>');


		$edit->pre_process('insert'  ,'_valida');
		$edit->pre_process('update'  ,'_valida');
		//$edit->post_process('insert'  ,'_paiva');
		//$edit->post_process('update'  ,'_paiva');

		$edit->numero  = new inputField("N&uacute;mero", "numero");
		$edit->numero->mode="autohide";
		$edit->numero->when=array('show');

		$edit->movi  = new inputField("Anticipo", "movi");
		$edit->movi->size=10;
		$edit->movi->rule="required";
		$edit->movi->append($pMOVI);

		$edit->tipo = new dropdownField("Orden de ", "tipo");
		$edit->tipo->option("Compra"  ,"Compra");
		$edit->tipo->option("Servicio","Servicio");
		$edit->tipo->style="width:100px;";

		$edit->fecha = new  dateonlyField("Fecha",  "fecha");
		$edit->fecha->insertValue = date('Y-m-d');
		$edit->fecha->size =12;

		$edit->factura  = new inputField("Factura", "factura");
		$edit->factura->size=15;
		$edit->factura->rule="required";

		$edit->controlfac  = new inputField("Control Factura", "controlfac");
		$edit->controlfac->size=15;
		$edit->controlfac->rule="required";

		$edit->uejecutora = new dropdownField("Unidad Ejecutora", "uejecutora");
		$edit->uejecutora->option("","Seccionar");
		$edit->uejecutora->options("SELECT codigo, nombre FROM uejecutora ORDER BY nombre");
		//$edit->uejecutora->onchange = "get_uadmin();";
		$edit->uejecutora->rule = "required";

		$edit->estadmin = new dropdownField("Estructura Administrativa","estadmin");
		$edit->estadmin->option("","Seleccione");
		$edit->estadmin->rule='required';
		$edit->estadmin->options("SELECT b.codigo, CONCAT_WS(' ',b.codigo,b.denominacion) AS val FROM presupuesto AS a JOIN estruadm AS b ON a.codigoadm=b.codigo  GROUP BY b.codigo");

		$edit->fondo = new dropdownField("Fondo", "fondo");
		$edit->fondo->rule = "required";
		$estadmin=$edit->getval('estadmin');
		if($estadmin!==false){
			$edit->fondo->options("SELECT tipo,tipo a  FROM presupuesto WHERE codigoadm='$estadmin' GROUP BY tipo");
		}else{
			$edit->fondo->option("","Seleccione una estructura administrativa primero");
		}

		$edit->cod_prov = new inputField("Beneficiario", 'cod_prov');
		$edit->cod_prov->size     = 6;
		$edit->cod_prov->rule     = "required";
		$edit->cod_prov->append($bSPRV);
		$edit->cod_prov->readonly=true;

		$edit->nombre = new inputField("Nombre", 'nombre');
		$edit->nombre->size = 50;
		$edit->nombre->readonly = true;

		$edit->reteiva_prov  = new inputField("reteiva_prov", "reteiva_prov");
		$edit->reteiva_prov->size=1;
		//$edit->reteiva_prov->mode="autohide";
		$edit->reteiva_prov->when=array('modify','create');

		$edit->beneficiario = new inputField("Beneficiario", 'beneficiario');
		$edit->beneficiario->size = 50;
		//$edit->beneficiario->rule = "required";

		$edit->creten = new dropdownField("Codigo ISLR","creten");
		$edit->creten->option("","");
		$edit->creten->options("SELECT codigo,CONCAT_WS(' ',codigo,activida) FROM rete ORDER BY codigo");
		$edit->creten->style="width:300px;";
		$edit->creten->onchange ='cal_islr();';

		$edit->observa = new textAreaField("Observaciones", 'observa');
		$edit->observa->cols = 106;
		$edit->observa->rows = 3;

		//$edit->tcantidad = new inputField("tcantidad", 'tcantidad');
		//$edit->tcantidad->size = 8;

		$edit->subtotal = new inputField("Sub Total", 'subtotal');
		$edit->subtotal->css_class='inputnum';
		$edit->subtotal->size = 8;

		$edit->ivaa = new inputField("IVA Sobre Tasa", 'ivaa');
		$edit->ivaa->css_class='inputnum';
		$edit->ivaa->size = 8;

		$edit->ivag = new inputField("IVA Tasa General", 'ivag');
		$edit->ivag->css_class='inputnum';
		$edit->ivag->size = 8;

		$edit->ivar = new inputField("IVA Tasa reducida", 'ivar');
		$edit->ivar->css_class='inputnum';
		$edit->ivar->size = 8;

		$edit->exento = new inputField("Exento", 'exento');
		$edit->exento->css_class='inputnum';
		$edit->exento->size = 8;

		$edit->reteiva = new inputField("Retencion de IVA", 'reteiva');
		$edit->reteiva->css_class='inputnum';
		$edit->reteiva->size = 8;

		$edit->reten = new inputField("Retencion de ISLR", 'reten');
		$edit->reten->css_class='inputnum';
		$edit->reten->size = 8;

		$edit->total = new inputField("Total", 'total');
		$edit->total->css_class='inputnum';
		$edit->total->size = 8;

		$edit->itpartida = new inputField("(<#o#>) Partida", "partida_<#i#>");
		$edit->itpartida->rule='callback_repetido|required|callback_itpartida';
		$edit->itpartida->size=15;
		$edit->itpartida->append('<img src="/tortuga/assets/default/images/system-search.png"  alt="Busqueda de partidas" title="Busqueda de partidas" border="0" onclick="modbusdepen(<#i#>)"/>');
		$edit->itpartida->db_name='partida';
		$edit->itpartida->rel_id ='itodirect';
		//$edit->itpartida->readonly =true;

		$edit->itdescripcion = new inputField("(<#o#>) Descripci&oacute;n", "descripcion_<#i#>");
		$edit->itdescripcion->db_name  ='descripcion';
		$edit->itdescripcion->maxlength=80;
		$edit->itdescripcion->size     =30;
		$edit->itdescripcion->rule     = 'required';
		$edit->itdescripcion->rel_id   ='itodirect';

		$edit->itunidad = new dropdownField("(<#o#>) Unidad", "unidad_<#i#>");
		$edit->itunidad->db_name= 'unidad';
		$edit->itunidad->rule   = 'required';
		$edit->itunidad->rel_id = 'itodirect';
		$edit->itunidad->options("SELECT unidades AS id,unidades FROM unidad ORDER BY unidades");
		$edit->itunidad->style="width:80px";

		$edit->itcantidad = new inputField("(<#o#>) Cantidad", "cantidad_<#i#>");
		$edit->itcantidad->css_class='inputnum';
		$edit->itcantidad->db_name  ='cantidad';
		$edit->itcantidad->rel_id   ='itodirect';
		$edit->itcantidad->rule     ='numeric';
		$edit->itcantidad->onchange ='cal_importe(<#i#>);';
		$edit->itcantidad->size     =4;
		//$edit->itcantidad->insertValue=0;

		$edit->itprecio = new inputField("(<#o#>) Precio", "precio_<#i#>");
		$edit->itprecio->css_class='inputnum';
		$edit->itprecio->db_name  ='precio';
		$edit->itprecio->rel_id   ='itodirect';
		$edit->itprecio->rule     ='numeric';
		$edit->itprecio->onchange ='cal_importe(<#i#>);';
		$edit->itprecio->size     =8;
		//$edit->itprecio->insertValue=0;

		$edit->itiva = new dropdownField("(<#o#>) IVA", "iva_<#i#>");
		$edit->itiva->db_name  ='iva';
		$edit->itiva->rel_id   ='itodirect';
		$edit->itiva->onchange ='cal_importe(<#i#>);';
		$edit->itiva->option("0"  ,"Excento");
		$edit->itiva->options($this->_ivaplica());
		$edit->itiva->style    ="width:80px";

		$edit->itimporte = new inputField("(<#o#>) Importe", "importe_<#i#>");
		$edit->itimporte->css_class='inputnum';
		$edit->itimporte->db_name  ='importe';
		$edit->itimporte->rel_id   ='itodirect';
		$edit->itimporte->rule     ='numeric';
		$edit->itimporte->readonly =true;
		$edit->itimporte->size     =8;

		$status=$edit->get_from_dataobjetct('status');
		if($status=='P'){
			$action = "javascript:window.location='" .site_url($this->url.'/actualizar/'.$edit->rapyd->uri->get_edited_id()). "'";
			$edit->button_status("btn_status",'Actualizar',$action,"TR","show");
			$edit->buttons("modify","delete","save");
		}elseif($status=='C'){
			$action = "javascript:window.location='" .site_url($this->url.'/reversar/'.$edit->rapyd->uri->get_edited_id()). "'";
			$edit->button_status("btn_rever",'Reversar',$action,"TR","show");
		}else{
			$edit->buttons("save");
		}

		$edit->buttons("undo","back","add_rel");
		$edit->build();

		//SELECT codigo,base1,tari1,pama1 FROM rete
		$query = $this->db->query('SELECT codigo,base1,tari1,pama1 FROM rete');

		$rt=array();
		foreach ($query->result_array() as $row){
			$pivot=array('base1'=>$row['base1'],
			             'tari1'=>$row['tari1'],
			             'pama1'=>$row['pama1']);
			$rt['_'.$row['codigo']]=$pivot;
		}
		$rete=json_encode($rt);

		$conten['rete']=$rete;
		$ivaplica=$this->ivaplica2();
		$conten['ivar']=$ivaplica['redutasa'];
		$conten['ivag']=$ivaplica['tasa'];
		$conten['ivaa']=$ivaplica['sobretasa'];
		$smenu['link']=barra_menu('101');
		$data['smenu'] = $this->load->view('view_sub_menu', $smenu,true);
		$conten["form"]  =&  $edit;
		$data['content'] = $this->load->view('view_odirect', $conten,true);
		//$data['content'] = $edit->output;
		$data['title']   = " $this->tits ";
		$data["head"]    = $this->rapyd->get_head().script('jquery.js').script("plugins/jquery.numeric.pack.js").script("plugins/jquery.json.min.js");
		$this->load->view('view_ventanas', $data);
	}

	function ivaplica2($mfecha=NULL){
		if(empty($mfecha)) $mfecha=date('Ymd');
		$CI =& get_instance();
		$qq = $CI->db->query("SELECT tasa, redutasa, sobretasa FROM civa WHERE fecha < '$mfecha' ORDER BY fecha DESC LIMIT 1");
		$rr = $qq->row_array();
		//$aa = each($rr);
		return $rr;
	}

	function repetido($partida){
		if(isset($this->__rpartida)){
			if(in_array($partida, $this->__rpartida)){
				$this->validation->set_message('repetido',"El rublo %s ($partida) esta repetido");
				return false;
			}
		}
		$this->__rpartida[]=$partida;
		return true;
	}

	function itpartida($partida){
		$estadmin = $this->db->escape($this->input->post('estadmin'));
		$fondo    = $this->db->escape($this->input->post('fondo'));
		$partida  = $this->db->escape($partida);
		$cana=$this->datasis->dameval("SELECT COUNT(*) FROM presupuesto WHERE codigoadm=$estadmin AND codigopres=$partida AND tipo=$fondo");
		if($cana>0){
			return true;
		}else{
			$this->validation->set_message('itpartida',"La partida %s ($partida) No pertenece al la estructura administrativa o al fondo seleccionado");
			return false;
		}
	}

	function actualizar($id){
		$this->rapyd->load('dataobject');

		$do = new DataObject("odirect");
		$do->rel_one_to_many('itodirect', 'itodirect', array('numero'=>'numero'));
		$do->load($id);
		$codigoadm   = $do->get('estadmin');
		$fondo       = $do->get('fondo');
		$movi        = $do->get('movi');

		$movi = new DataObject("movi");
		$movi->rel_one_to_many('itmovi', 'itmovi', array('numero'=>'numero'));
		$movi->load($movi);
		$mmonto = $movi->get('monto');
		$msaldo = $do->get('saldo');

		$presup = new DataObject("presupuesto");
		$pk=array('codigoadm'=>$codigoadm,'tipo'=>$fondo);
		$error='';
		$tiva =0;
		$tmont=0;
		$partidaiva=$this->datasis->traevalor("PARTIDAIVA");

		$sta=$do->get('status');

		if($sta=="P"){
			for($i=0;$i < $do->count_rel('itodirect');$i++){
				$codigopres  = $do->get_rel('itodirect','partida',$i);
				$importe     = $do->get_rel('itodirect','importe',$i);
				$iva         = $do->get_rel('itodirect','iva'    ,$i);
				$m           = ($importe*(($iva+100)/100))-$importe;
				$tiva       += $m;
				$tmont+=$mont = $importe;

				$pk['codigopres'] = $codigopres;

				$presup->load($pk);
				$asignacion   = $presup->get("asignacion");
				$aumento      = $presup->get("aumento");
				$disminucion  = $presup->get("disminucion");
				$traslados    = $presup->get("traslados");
				$comprometido = $presup->get("comprometido");

				$disponible=(($asignacion+$aumento-$disminucion)+($traslados))-$comprometido;
				if($mont > $disponible)
					$error.="<div class='alert'><p>No se Puede Completar la Transaccion debido a que el monto del $this->tits ($mont) es mayor al monto disponible($disponible) para la partida: $codigopres</p></div>";

			}

			$pk['codigopres'] = $partidaiva;
			$presup->load($pk);
			$asignacion   = $presup->get("asignacion");
			$aumento      = $presup->get("aumento");
			$disminucion  = $presup->get("disminucion");
			$traslados    = $presup->get("traslados");
			$comprometido = $presup->get("comprometido");

			$disponible=(($asignacion+$aumento-$disminucion)+($traslados))-$comprometido;

			if($tiva > $disponible)
					$error.="<div class='alert'><p>El monto de iva ($tiva) de la orden de compra, es mayor al monto disponible ($disponible) para la partida de iva ($partidaiva)</p></div>";

			if($tmont > $mmonto-$msaldo)
				$error.="<div class='alert'><p>El monto ($tmont) de la orden de pago directa, es mayor al monto disponible ($mmonto-$msaldo) para el anticipo ($movi)</p></div>";

			if(empty($error)){
				$tiva=0;
				for($i=0;$i < $do->count_rel('itodirect');$i++){
					$codigopres  = $do->get_rel('itodirect','partida',$i);
					$importe     = $do->get_rel('itodirect','importe',$i);
					$iva         = $do->get_rel('itodirect','iva'    ,$i);
					$m           = ($importe*(($iva+100)/100))-$importe;
					$tiva+=$m;
					$mont        = $importe;

					$pk['codigopres'] = $codigopres;

					$presup->load($pk);
					$comprometido=$presup->get("comprometido");
					$causado     =$presup->get("causado");
					$opago       =$presup->get("opago");
					$pagado      =$presup->get("pagado");

					$comprometido+=$mont;
					$causado     +=$mont;
					$opago       +=$mont;
					$pagado      +=$mont;

					$presup->set("comprometido",$comprometido);
					$presup->set("causado"     ,$causado);
					$presup->set("opago"       ,$opago);
					$presup->set("pagado"      ,$pagado);

					$presup->save();
				}

				$pk['codigopres'] = $partidaiva;
				$presup->load($pk);

				$comprometido=$presup->get("comprometido");
				$causado     =$presup->get("causado");
				$opago       =$presup->get("opago");
				$pagado      =$presup->get("pagado");

				$comprometido+=$tiva;
				$causado     +=$tiva;
				$opago       +=$tiva;
				$pagado      +=$tiva;

				$presup->set("comprometido",$comprometido);
				$presup->set("causado"     ,$causado);
				$presup->set("opago"       ,$opago);
				$presup->set("pagado"      ,$pagado);

				$presup->save();

				$do->set('status','C');
				$do->save();
			}
		}

		if(empty($error)){
			redirect($this->url."/dataedit/show/$id");
		}else{
			$data['content'] = $error.anchor($this->url."/dataedit/show/$id",'Regresar');
			$data['title']   = " $this->tits ";
			$data["head"]    = $this->rapyd->get_head().script('jquery.js').script("plugins/jquery.numeric.pack.js");
			$this->load->view('view_ventanas', $data);
		}
	}

	function reversar($id){
		$this->rapyd->load('dataobject');

		$do = new DataObject("odirect");
		$do->rel_one_to_many('itodirect', 'itodirect', array('numero'=>'numero'));
		$do->load($id);

		$sta=$do->get('status');
		if($sta=='C'){
			$codigoadm   = $do->get('estadmin');
			$fondo       = $do->get('fondo');

			$presup = new DataObject("presupuesto");
			$pk=array('codigoadm'=>$codigoadm,'tipo'=>$fondo);
			$error='';
			$tiva=0;
			for($i=0;$i < $do->count_rel('itodirect');$i++){
				$codigopres  = $do->get_rel('itodirect','partida',$i);
				$importe     = $do->get_rel('itodirect','importe',$i);
				$iva         = $do->get_rel('itodirect','iva'    ,$i);
				$m           = ($importe*(($iva+100)/100))-$importe;
				$tiva+=$m;
				$mont        = $importe;

				$pk['codigopres'] = $codigopres;

				$presup->load($pk);
				$comprometido=$presup->get("comprometido");
				$causado     =$presup->get("causado");
				$opago       =$presup->get("opago");
				$pagado      =$presup->get("pagado");

				$comprometido-=$mont;
				$causado     -=$mont;
				$opago       -=$mont;
				$pagado      -=$mont;

				$presup->set("comprometido",$comprometido);
				$presup->set("causado"     ,$causado);
				$presup->set("opago"       ,$opago);
				$presup->set("pagado"      ,$pagado);

				$presup->save();
			}

			$partidaiva=$this->datasis->traevalor("PARTIDAIVA");
			$pk['codigopres'] = $partidaiva;
			$presup->load($pk);

			$comprometido=$presup->get("comprometido");
			$causado     =$presup->get("causado");
			$opago       =$presup->get("opago");
			$pagado      =$presup->get("pagado");

			$comprometido-=$tiva;
			$causado     -=$tiva;
			$opago       -=$tiva;
			$pagado      -=$tiva;

			$presup->set("comprometido",$comprometido);
			$presup->set("causado"     ,$causado);
			$presup->set("opago"       ,$opago);
			$presup->set("pagado"       ,$pagado);

			$presup->save();



			$do->set('status','P');
			$do->save();
		}

		redirect($this->url."/dataedit/show/$id");
	}

	function _ivaplica($mfecha=NULL){
    if(empty($mfecha)) $mfecha=date('Ymd');
    $qq = $this->datasis->damerow("SELECT tasa AS g, redutasa AS r, sobretasa AS a FROM civa WHERE fecha < '$mfecha' ORDER BY fecha DESC LIMIT 1");
    $rr=array();
    foreach ($qq AS $val){
            $rr[$val]=$val.'%';
    }
    $rr['0']='0%';
    return $rr;
	}

	function _valida($do){

		$rr=$this->ivaplica2();
		$reteiva_prov=$do->get('reteiva_prov');
		$creten      =$do->get('creten');

		$error='';
		$giva=$aiva=$riva=$exento=$reteiva=$subtotal=0;
		for($i=0;$i < $do->count_rel('itodirect');$i++){

			$partida    = $do->get_rel('itodirect','partida'    ,$i);
			$cantidad   = $do->get_rel('itodirect','cantidad'   ,$i);
			$precio     = $do->get_rel('itodirect','precio'     ,$i);
			$piva       = $do->get_rel('itodirect','iva'        ,$i);
			$importe    = $precio * $cantidad;

			$imp        = $do->set_rel('itodirect','importe' ,$importe,$i);
			$subtotal+=$importe;

			if($piva==$rr['tasa']     )$giva+=($rr['tasa']     *$importe)/100;
			if($piva==$rr['redutasa'] )$riva+=($rr['redutasa'] *$importe)/100;
			if($piva==$rr['sobretasa'])$aiva+=($rr['sobretasa']*$importe)/100;

			if($piva==0)$exento+=$importe;
		}
		if($reteiva_prov!=75)$reteiva_prov=100;

		$partidaiva=$this->datasis->traevalor("PARTIDAIVA");
		$reteiva=(($giva+$riva+$aiva)*$reteiva_prov)/100;


		$rete=$this->datasis->damerow("SELECT base1,tari1,pama1 FROM rete WHERE codigo='$creten'");

		if($rete){
			if(substr($creten,1,1)=='1')$reten=round($subtotal*$rete['base1']*$rete['tari1']/10000,2);
			else $reten=round(($subtotal-$rete['pama1'])*$rete['base1']*$rete['tari1']/10000,2);
			if($reten < 0)$reten=0;
			$do->set('reten'     ,    $reten     );
		}

		$do->set('ivag'      ,    $giva      );
		$do->set('ivar'      ,    $riva      );
		$do->set('ivaa'      ,    $aiva      );
		$do->set('subtotal'  ,    $subtotal  );
		$do->set('exento'    ,    $exento    );
		$do->set('reteiva'   ,    $reteiva   );
		$do->set('total'     ,    $subtotal+$giva+$riva+$aiva     );


	}

	function tari1(){
		$creten=$this->db->escape($this->input->post('creten'));
		$a=$this->datasis->damerow("SELECT base1,tari1,pama1 FROM rete WHERE codigo=$creten");
		echo json_encode($a);
	}
}
?>

