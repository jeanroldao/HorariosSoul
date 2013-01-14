<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');

set_time_limit(0);

include './simplehtmldom/simple_html_dom.php';

class WebHelper {

	protected function getContentsFromUrl($url) {
		return file_get_html($url);
	}
}

class Option extends WebHelper {

	public $text;
	public $value;
	
	public function __construct($value, $text) {
		$this->value = $value;
		$this->text = $text;
	}
	
	public function __toString() {
		return $this->text;
	}
	
	public function toString() {
		return $this->__toString();
	}
}

class Sentido extends Option {

	private $linhas = null;
	
	public function getLinhas() {
		if ($this->linhas != null) {
			return $this->linhas;
		} else {
			$this->linhas = array();
			
			$htmlSentido = $this->getContentsFromUrl('http://www.soul.com.br/site/itinerarios.php?sentido='.$this->value);
			
			
			foreach ($htmlSentido->find('#linha option') as $linha) {
				if ($linha->value) {
					$dias = array();
					foreach ($htmlSentido->find('#dia option') as $dia) {
						if ($dia->value) {
							$dias[] = new Dia($dia->value, $dia->text());
						}
					}
					$this->linhas[] = new Linha($linha->value, $linha->text(), /*sentido = */$this, $dias);
				}
			}
			return $this->linhas;
		}
	}
}

class Linha extends Option {

	private $dias = null;
	public $sentido = null;
	
	public function __construct($value, $text, Sentido $sentido, array $dias = array()) {
		parent::__construct($value, $text);
		$this->sentido = $sentido;
		$this->setDias($dias);
	}
	
	public function getDias() {
		if ($this->dias != null) {
			return $this->dias;
		} else {
			$this->dias = array();
			//load
			return $this->dias;
		}
	}
	public function setDias(array $dias) {
		$this->dias = $dias;
		foreach ($this->dias as $dia) {
			$dia->setLinha($this);
		}
	}
}

class Dia extends Option {

	private $horarios = null;
	private $linha;
	
	public function __construct($value, $text, Linha $linha = null) {
		parent::__construct($value, $text);
		$this->linha = $linha;
	}
	
	public function getLinha() {
		return $this->linha;
	}
	public function setLinha(Linha $linha = null) {
		$this->linha = $linha;
	}
	
	public function getHorarios() {
		if ($this->horarios != null) {
			return $this->horarios;
		} else {
			$this->horarios = array();
			$sentido = $this->linha->sentido->value;
			$linha = $this->linha->value;
			$dia = $this->value;
			$htmlTabela = $this->getContentsFromUrl('http://www.soul.com.br/site/itinerarios.php?sentido='.$sentido.'&linha='.rawurlencode($linha).'&dia='.$dia);
			foreach ($htmlTabela->find('tr[bgcolor]') as $linha) {
				$colunas = $linha->find('td');
				
				$hora = $colunas[0]->text();
				$desc = trim(str_replace('&nbsp;', ' ', $colunas[1]->text()));
				
				$this->horarios[] = new Horario($this->linha->sentido->text, $this->linha->text, $this->text, $hora, $desc);
			}
			return $this->horarios;
		}
	}
}

class Horario extends WebHelper {

	public $sentido = '';
	public $linha = '';
	public $dia = '';
	public $hora = '';
	public $descricao = '';
	//public $itinerario = array();
	
	public function __construct($sentido, $linha, $dia, $hora, $descricao) {
		$this->sentido = $sentido;
		$this->linha = $linha;
		$this->dia = $dia;
		$this->hora = $hora;
		$this->descricao = $descricao;
	}
	
	public function getLinhaTabela() {
		return (array)$this;
	}
}

class Soul extends WebHelper {

	private $sentidos = array();
	
	public function __construct() {
		$horarios = $this->loadFromSoul();
		echo json_encode($horarios);
	}
	
	private function loadFromSoul() {
		$tabelahorarios = array();
		
		foreach ($this->loadSentidos() as $sentido) {
			foreach ($sentido->getLinhas() as $linha) {
				foreach ($linha->getDias() as $dia) {
					foreach ($dia->getHorarios() as $horario) {
						$tabelahorarios[] = $horario->getLinhaTabela();
						if (count($tabelahorarios) > 10) {break 4;}
					}
				}
			}
		}
		return $tabelahorarios;
	}
	
	private function loadSentidos() {
	
		$this->sentidos = array();
		$html = $this->getContentsFromUrl('http://www.soul.com.br/site/itinerarios.php?');
		
		foreach ($html->find('#sentido option') as $opt) {
			if (in_array($opt->plaintext, array('ALV-POA', 'POA-ALV'))) {
				$this->sentidos[] = new Sentido($opt->value, $opt->text());
			}
		}
		return $this->sentidos;
		
	}
}
$soul = new Soul();