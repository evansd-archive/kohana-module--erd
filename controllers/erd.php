<?php
class ERD_Controller extends Controller
{
	const ALLOW_PRODUCTION = FALSE;
	
	public function index()
	{
		
		$dotfile = "digraph G {\n";
		
		foreach($this->_get_models() as $model)
		{
			$dotfile .= $model->object_name.' [shape="Mrecord", label=< <FONT POINT-SIZE="18.0">'.$model->object_name.'</FONT><BR ALIGN="CENTER"/>';
			
			foreach($model->table_columns as $column => $meta)
			{
				if(substr($column, -3) == '_id' OR $column == 'id') continue;
				$dotfile .= '<FONT COLOR="darkgreen">'.$column.'</FONT> <FONT COLOR="grey">('.$meta['type'].')'.'</FONT><BR ALIGN="LEFT"/>';
			}
			
			$dotfile .= '>];'."\n";
			
			foreach($model->has_one as $related)
			{
				$dotfile .= $model->object_name.' -> '.$related.";\n"; //" [arrowhead=\"tee\"];\n";
			}
			
			foreach($model->has_many as $related)
			{
				$dotfile .= $model->object_name.' -> '.inflector::singular($related).";\n"; //" [arrowhead=\"crow\"];\n";
			}
			
			
		}
		
		$dotfile .= '}';
		
		return $this->_render($dotfile);
		
		foreach($this->_get_models() as $model)
		{
			echo $model->object_name.'<br />';
			foreach($model->table_columns as $column => $meta)
			{
				echo ' : '.$column.'<br />';
			}
			echo '<br />';
		}
	}
	
	
	public function _get_models()
	{
		$initial_classes = get_declared_classes();
		
		foreach(Kohana::list_files('models') as $file)
		{
			include_once($file);
		}
		
		$classes = array_diff(get_declared_classes(), $initial_classes);
		
		$models = array();
		
		foreach($classes as $class)
		{
			if(is_subclass_of($class, 'ORM'))
			{
				$models[] = new $class;
			}
		}
		
		return $models;
	}
	
	
	public function _render($dotfile)
	{
		$descriptorspec = array(
		   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		   2 => array("pipe", "w"),  // sterr is a pipe that the child will write to
		);

		$process = proc_open("dot -Tpng", $descriptorspec, $pipes);

		if (is_resource($process)) {
		    // $pipes now looks like this:
		    // 0 => writeable handle connected to child stdin
		    // 1 => readable handle connected to child stdout

		    fwrite($pipes[0], $dotfile);
		    fclose($pipes[0]);

		    $output = stream_get_contents($pipes[1]);
		    fclose($pipes[1]);
		    
		    $error = stream_get_contents($pipes[2]);
		    fclose($pipes[2]);

		    // It is important that you close any pipes before calling
		    // proc_close in order to avoid a deadlock
		    if(proc_close($process) != 0) {
				throw new Kohana_User_Exception('Graphviz Error', $error);
			}

		}
		
		header('Content-Type: image/png');
		
		echo $output;
		
	}
}
