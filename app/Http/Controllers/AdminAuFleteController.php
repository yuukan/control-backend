<?php namespace App\Http\Controllers;

	use Session;
	use Request;
	use DB;
	use CRUDBooster;

	class AdminAuFleteController extends \crocodicstudio\crudbooster\controllers\CBController {

	    public function cbInit() {

			# START CONFIGURATION DO NOT REMOVE THIS LINE
			$this->title_field = "id";
			$this->limit = "300";
			$this->orderby = "id,desc";
			$this->global_privilege = false;
			$this->button_table_action = true;
			$this->button_bulk_action = true;
			$this->button_action_style = "button_icon";
			$this->button_add = true;
			$this->button_edit = true;
			$this->button_delete = true;
			$this->button_detail = true;
			$this->button_show = true;
			$this->button_filter = true;
			$this->button_import = false;
			$this->button_export = false;
			$this->table = "au_flete";
			# END CONFIGURATION DO NOT REMOVE THIS LINE

			# START COLUMNS DO NOT REMOVE THIS LINE
			$this->col = [];
			$this->col[] = ["label"=>"Número Unidad","name"=>"NumeroUnidad"];
			$this->col[] = ["label"=>"Placa","name"=>"Placa"];
			$this->col[] = ["label"=>"Nit","name"=>"Nit"];
			$this->col[] = ["label"=>"Codigo SAP","name"=>"CodigoSAP"];
			$this->col[] = ["label"=>"Status","name"=>"au_status_id","join"=>"au_status,NombreStatus"];
			# END COLUMNS DO NOT REMOVE THIS LINE

			# START FORM DO NOT REMOVE THIS LINE
			$this->form = [];
			$this->form[] = ['label'=>'Número Unidad','name'=>'NumeroUnidad','type'=>'text','validation'=>'required','width'=>'col-sm-9'];
			$this->form[] = ['label'=>'Código Energía  Y Minas','name'=>'CodigoEnergiaYMinas','type'=>'text','validation'=>'','width'=>'col-sm-10'];
			$this->form[] = ['label'=>'Placa','name'=>'Placa','type'=>'text','validation'=>'','width'=>'col-sm-10'];
			$this->form[] = ['label'=>'Nit','name'=>'Nit','type'=>'text','validation'=>'','width'=>'col-sm-10'];
			$this->form[] = ['label'=>'Código SAP','name'=>'CodigoSAP','type'=>'text','validation'=>'required','width'=>'col-sm-10'];
			$this->form[] = ['label'=>'Licencia Camión','name'=>'LicenciaCamion','type'=>'text','validation'=>'','width'=>'col-sm-10'];
			$this->form[] = ['label'=>'Fecha Licencia Camión','name'=>'FechaLicenciaCamion','type'=>'date','validation'=>'','width'=>'col-sm-10'];
			$this->form[] = ['label'=>'Calcomanía Seguro','name'=>'CalcomaniaSeguro','type'=>'text','validation'=>'','width'=>'col-sm-10'];
			$this->form[] = ['label'=>'Fecha Vence Seguro','name'=>'FechaVenceSeguro','type'=>'date','validation'=>'','width'=>'col-sm-10','datatable'=>'au_status,NombreStatus'];
			$this->form[] = ['label'=>'Status','name'=>'au_status_id','type'=>'select2','validation'=>'','width'=>'col-sm-10','datatable'=>'au_status,NombreStatus'];
			$this->form[] = ['label'=>'Comentario','name'=>'Comentario','type'=>'wysiwyg','validation'=>'','width'=>'col-sm-10'];
			// $this->form[] = ['label'=>'Cantidad Compartimentos','name'=>'CantidadCompartimentos','type'=>'number','validation'=>'','width'=>'col-sm-10','table'=>'au_compartimientos','foreign_key'=>'au_flete_id'];

			$columns[] =['label'=>'Nombre','name'=>'nombre','type'=>'text','validation'=>'required','width'=>'col-sm-10'];;
			$columns[] = ['label'=>'Orden','name'=>'Orden','type'=>'text','validation'=>'required','width'=>'col-sm-10'];
			$columns[] = ['label'=>'CantidadGalones','name'=>'CantidadGalones','type'=>'text','validation'=>'required','width'=>'col-sm-10'];

			$this->form[] = ['label'=>'Compartimientos','name'=>'compartimientos','type'=>'child','columns'=>$columns,'table'=>'au_compartimientos','foreign_key'=>'au_flete_id','width'=>'col-sm-10'];
			# END FORM DO NOT REMOVE THIS LINE

			# OLD START FORM
			//$this->form = [];
			//$this->form[] = ['label'=>'Código Energía  Y Minas','name'=>'CodigoEnergiaYMinas','type'=>'text','validation'=>'','width'=>'col-sm-10'];
			//$this->form[] = ['label'=>'Placa','name'=>'Placa','type'=>'text','validation'=>'','width'=>'col-sm-10'];
			//$this->form[] = ['label'=>'Nit','name'=>'Nit','type'=>'text','validation'=>'','width'=>'col-sm-10'];
			//$this->form[] = ['label'=>'Código SAP','name'=>'CodigoSAP','type'=>'text','validation'=>'','width'=>'col-sm-10'];
			//$this->form[] = ['label'=>'Licencia Camión','name'=>'LicenciaCamion','type'=>'text','validation'=>'','width'=>'col-sm-10'];
			//$this->form[] = ['label'=>'Fecha Licencia Camión','name'=>'FechaLicenciaCamion','type'=>'date','validation'=>'required|date','width'=>'col-sm-10'];
			//$this->form[] = ['label'=>'Calcomanía Seguro','name'=>'CalcomaniaSeguro','type'=>'text','validation'=>'','width'=>'col-sm-10'];
			//$this->form[] = ['label'=>'Fecha Vence Seguro','name'=>'FechaVenceSeguro','type'=>'date','validation'=>'required|date','width'=>'col-sm-10'];
			//$this->form[] = ['label'=>'Au Status Id','name'=>'au_status_id','type'=>'select2','validation'=>'required|integer|min:0','width'=>'col-sm-10','datatable'=>'au_status,NombreStatus'];
			//$this->form[] = ['label'=>'Comentario','name'=>'Comentario','type'=>'wysiwyg','validation'=>'','width'=>'col-sm-10'];
			//$this->form[] = ['label'=>'Cantidad Compartimentos','name'=>'CantidadCompartimentos','type'=>'number','validation'=>'required|integer|min:0','width'=>'col-sm-10'];
			//
			//$columns[] = ['label'=>'Nombre','name'=>'nombre','type'=>'text','required'=>true];
			//$columns[] = ['label'=>'Orden','name'=>'Orden','type'=>'number','required'=>true];
			//$columns[] = ['label'=>'Cantidad de Galones','name'=>'CantidadGalones','type'=>'number','required'=>true];
			//$columns[] = ['label'=>'Comentario','name'=>'comentario','type'=>'text'];
			//
			//$this->form[] = ['label'=>'Compartimientos','name'=>'compartimientos','type'=>'child','columns'=>$columns,'table'=>'au_compartimientos','foreign_key'=>'au_flete_id'];
			# OLD END FORM

			/* 
	        | ---------------------------------------------------------------------- 
	        | Sub Module
	        | ----------------------------------------------------------------------     
			| @label          = Label of action 
			| @path           = Path of sub module
			| @foreign_key 	  = foreign key of sub table/module
			| @button_color   = Bootstrap Class (primary,success,warning,danger)
			| @button_icon    = Font Awesome Class  
			| @parent_columns = Sparate with comma, e.g : name,created_at
	        | 
	        */
	        $this->sub_module = array();


	        /* 
	        | ---------------------------------------------------------------------- 
	        | Add More Action Button / Menu
	        | ----------------------------------------------------------------------     
	        | @label       = Label of action 
	        | @url         = Target URL, you can use field alias. e.g : [id], [name], [title], etc
	        | @icon        = Font awesome class icon. e.g : fa fa-bars
	        | @color 	   = Default is primary. (primary, warning, succecss, info)     
	        | @showIf 	   = If condition when action show. Use field alias. e.g : [id] == 1
	        | 
	        */
	        $this->addaction = array();


	        /* 
	        | ---------------------------------------------------------------------- 
	        | Add More Button Selected
	        | ----------------------------------------------------------------------     
	        | @label       = Label of action 
	        | @icon 	   = Icon from fontawesome
	        | @name 	   = Name of button 
	        | Then about the action, you should code at actionButtonSelected method 
	        | 
	        */
	        $this->button_selected = array();

	                
	        /* 
	        | ---------------------------------------------------------------------- 
	        | Add alert message to this module at overheader
	        | ----------------------------------------------------------------------     
	        | @message = Text of message 
	        | @type    = warning,success,danger,info        
	        | 
	        */
	        $this->alert        = array();
	                

	        
	        /* 
	        | ---------------------------------------------------------------------- 
	        | Add more button to header button 
	        | ----------------------------------------------------------------------     
	        | @label = Name of button 
	        | @url   = URL Target
	        | @icon  = Icon from Awesome.
	        | 
	        */
	        $this->index_button = array();



	        /* 
	        | ---------------------------------------------------------------------- 
	        | Customize Table Row Color
	        | ----------------------------------------------------------------------     
	        | @condition = If condition. You may use field alias. E.g : [id] == 1
	        | @color = Default is none. You can use bootstrap success,info,warning,danger,primary.        
	        | 
	        */
	        $this->table_row_color = array();     	          

	        
	        /*
	        | ---------------------------------------------------------------------- 
	        | You may use this bellow array to add statistic at dashboard 
	        | ---------------------------------------------------------------------- 
	        | @label, @count, @icon, @color 
	        |
	        */
	        $this->index_statistic = array();



	        /*
	        | ---------------------------------------------------------------------- 
	        | Add javascript at body 
	        | ---------------------------------------------------------------------- 
	        | javascript code in the variable 
	        | $this->script_js = "function() { ... }";
	        |
	        */
	        $this->script_js = NULL;


            /*
	        | ---------------------------------------------------------------------- 
	        | Include HTML Code before index table 
	        | ---------------------------------------------------------------------- 
	        | html code to display it before index table
	        | $this->pre_index_html = "<p>test</p>";
	        |
	        */
	        $this->pre_index_html = null;
	        
	        
	        
	        /*
	        | ---------------------------------------------------------------------- 
	        | Include HTML Code after index table 
	        | ---------------------------------------------------------------------- 
	        | html code to display it after index table
	        | $this->post_index_html = "<p>test</p>";
	        |
	        */
	        $this->post_index_html = null;
	        
	        
	        
	        /*
	        | ---------------------------------------------------------------------- 
	        | Include Javascript File 
	        | ---------------------------------------------------------------------- 
	        | URL of your javascript each array 
	        | $this->load_js[] = asset("myfile.js");
	        |
	        */
	        $this->load_js = array();
	        
	        
	        
	        /*
	        | ---------------------------------------------------------------------- 
	        | Add css style at body 
	        | ---------------------------------------------------------------------- 
	        | css code in the variable 
	        | $this->style_css = ".style{....}";
	        |
	        */
	        $this->style_css = NULL;
	        
	        
	        
	        /*
	        | ---------------------------------------------------------------------- 
	        | Include css File 
	        | ---------------------------------------------------------------------- 
	        | URL of your css each array 
	        | $this->load_css[] = asset("myfile.css");
	        |
	        */
	        $this->load_css = array();
	        
	        
	    }


	    /*
	    | ---------------------------------------------------------------------- 
	    | Hook for button selected
	    | ---------------------------------------------------------------------- 
	    | @id_selected = the id selected
	    | @button_name = the name of button
	    |
	    */
	    public function actionButtonSelected($id_selected,$button_name) {
	        //Your code here
	            
	    }


	    /*
	    | ---------------------------------------------------------------------- 
	    | Hook for manipulate query of index result 
	    | ---------------------------------------------------------------------- 
	    | @query = current sql query 
	    |
	    */
	    public function hook_query_index(&$query) {
	        //Your code here
	            
	    }

	    /*
	    | ---------------------------------------------------------------------- 
	    | Hook for manipulate row of index table html 
	    | ---------------------------------------------------------------------- 
	    |
	    */    
	    public function hook_row_index($column_index,&$column_value) {	        
	    	//Your code here
	    }

	    /*
	    | ---------------------------------------------------------------------- 
	    | Hook for manipulate data input before add data is execute
	    | ---------------------------------------------------------------------- 
	    | @arr
	    |
	    */
	    public function hook_before_add(&$postdata) {        
	        //Your code here

	    }

	    /* 
	    | ---------------------------------------------------------------------- 
	    | Hook for execute command after add public static function called 
	    | ---------------------------------------------------------------------- 
	    | @id = last insert id
	    | 
	    */
	    public function hook_after_add($id) {        
	        //Your code here

	    }

	    /* 
	    | ---------------------------------------------------------------------- 
	    | Hook for manipulate data input before update data is execute
	    | ---------------------------------------------------------------------- 
	    | @postdata = input post data 
	    | @id       = current id 
	    | 
	    */
	    public function hook_before_edit(&$postdata,$id) {        
	        //Your code here

	    }

	    /* 
	    | ---------------------------------------------------------------------- 
	    | Hook for execute command after edit public static function called
	    | ----------------------------------------------------------------------     
	    | @id       = current id 
	    | 
	    */
	    public function hook_after_edit($id) {
	        //Your code here 

	    }

	    /* 
	    | ---------------------------------------------------------------------- 
	    | Hook for execute command before delete public static function called
	    | ----------------------------------------------------------------------     
	    | @id       = current id 
	    | 
	    */
	    public function hook_before_delete($id) {
	        //Your code here

	    }

	    /* 
	    | ---------------------------------------------------------------------- 
	    | Hook for execute command after delete public static function called
	    | ----------------------------------------------------------------------     
	    | @id       = current id 
	    | 
	    */
	    public function hook_after_delete($id) {
	        //Your code here

	    }



	    //By the way, you can still create your own method in here... :) 


	}