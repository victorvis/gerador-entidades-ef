<?php
$baseOrig = './zconverter';
$baseDest = './zconvertido';

function geraDtoSimples(&$MapeiaEntidadeParaDto, &$MapeiaDtoParaEntidade, &$jaUsouDadosControle, $baseOrig, $baseDest, $file, $pasta = '', $sufix = '') {
	$lines = file($baseOrig . '/' .$file);
	$insertDataControle = 0;
	$clsName = '';
	$startErasing = 0;
	$mapperAux1 = '';
	foreach ($lines as $key => $line) {
		if ($startErasing == 0) {
			if (preg_match_all('/public class ([A-Za-z0-9_]{0,})/', $line, $matches, PREG_SET_ORDER, 0)) {
				$a1 = str_replace('public class '.$matches[0][1], 'public class '.$matches[0][1].$sufix.'Dto', $line);
				$a1 = explode(":", $a1);
				$lines[$key] = $a1[0];				
				$clsName = $matches[0][1];
				$mapperAux2 = 'Mapper.CreateMap<'. $clsName .', '.$clsName.$sufix.'Dto>()';
				$mapperAux3 = 'Mapper.CreateMap<'.$clsName.$sufix.'Dto, '. $clsName .'>()';
			} elseif (stristr($line, 'namespace Procergs.Pro.Dominio.Entidades') !== false) {
				$package = 'namespace Procergs.Pro.Dto';
				if ($pasta) {
					$package .= '.'. $pasta;
				}
				$lines[$key] = str_replace('namespace Procergs.Pro.Dominio.Entidades', $package, $line);
			} elseif (stristr($line, 'public DateTime CtrDthInc { get; set; }')
					|| stristr($line, 'public DateTime CtrDthAtu { get; set; }')
					|| stristr($line, 'public DateTime? CtrDthAtu { get; set; }')
					|| stristr($line, 'public decimal CtrCodUsuInc { get; set; }')
					|| stristr($line, 'public decimal? CtrCodUsuAtu { get; set; }')
					|| stristr($line, 'public string CtrCodUsuInc { get; set; }')
					|| stristr($line, 'public string CtrCodUsuAtu { get; set; }')
			) {
				if ($insertDataControle == 0) {
					$lines[$key] = '        public virtual DadosControleDto DadosControle { get; set; }' . "\r\n";
					$insertDataControle = 1;
					if (!$jaUsouDadosControle) {
						$mapperAux1 = 'Mapper.CreateMap<'. $clsName .', DadosControleDto>();' .PHP_EOL;
						$jaUsouDadosControle = true;
					}
					$mapperAux2 .= ".ForMember(d => d.DadosControle, m => m.MapFrom(opt => opt))";
				} else {
					unset($lines[$key]);
				}
			} elseif (preg_match_all('/public virtual ICollection<([A-Za-z0-9_]{0,})> ([A-Za-z0-9_]{0,})/', $line, $matches, PREG_SET_ORDER, 0)) {
				$lines[$key] = str_replace('ICollection<'.$matches[0][1].'>', 'ICollection<'.$matches[0][1].'Dto>', $line);
				$mapperAux2 .= ".ForMember(d => d.". $matches[0][2] .", m => m.MapFrom(opt => opt.".$matches[0][2]."))";
			} elseif (preg_match_all('/public virtual ([A-Za-z0-9_]{0,}) ([A-Za-z0-9_]{0,})/', $line, $matches, PREG_SET_ORDER, 0)) {
				$lines[$key] = str_replace('public virtual '.$matches[0][1].' ', 'public virtual '.$matches[0][1].'Dto ', $line);
				$mapperAux2 .= ".ForMember(d => d.". $matches[0][2] .", m => m.MapFrom(opt => opt.".$matches[0][2]."))";
			} elseif ($clsName && preg_match_all('/public '.$clsName.'()/', $line, $matches, PREG_SET_ORDER, 0)) {
				$startErasing = 1;
				unset($lines[$key]);
			}
		} else {
			if (preg_match_all('/}'."\r\n".'/', $line, $matches, PREG_SET_ORDER, 0)) {
				$startErasing = 0;
			}
			unset($lines[$key]);
		}
	}
	$mapperAux2 .= ";" . PHP_EOL;
	$mapperAux3 .= ";" . PHP_EOL;
	$MapeiaEntidadeParaDto .= $mapperAux1 . $mapperAux2;
	$MapeiaDtoParaEntidade .= $mapperAux3;
	@mkdir($baseDest . '/' . $pasta, 777, true);
	$fd = fopen ($baseDest . '/' . $pasta . '/' . str_replace('.cs', $sufix.'Dto.cs', $file), "wb");
	foreach ($lines as $key => $line) {
		fwrite($fd, $line);
	}
	fclose ($fd);
}
$MapeiaEntidadeParaDto = '';
$MapeiaDtoParaEntidade = '';
if ($handle = opendir('./zconverter')) {
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			$jaUsouDadosControle = false;
			geraDtoSimples($MapeiaEntidadeParaDto, $MapeiaDtoParaEntidade, $jaUsouDadosControle, $baseOrig, $baseDest, $file);
			geraDtoSimples($MapeiaEntidadeParaDto, $MapeiaDtoParaEntidade, $jaUsouDadosControle, $baseOrig, $baseDest, $file, 'Listas', 'Lista');
			//geraDtoSimples($MapeiaEntidadeParaDto, $MapeiaDtoParaEntidade, $jaUsouDadosControle, $baseOrig, $baseDest, $file, 'Parametros', 'Param');
			geraDtoSimples($MapeiaEntidadeParaDto, $MapeiaDtoParaEntidade, $jaUsouDadosControle, $baseOrig, $baseDest, $file, 'Pesquisas', 'Pesq');
		}
	}
	closedir($handle);	
	$fd = fopen ($baseDest . '/mapeamento.cs', "wb");
	fwrite($fd, '==========================================MapeiaEntidadeParaDto'.PHP_EOL);
	fwrite($fd, $MapeiaEntidadeParaDto);
	fwrite($fd, '==========================================MapeiaDtoParaEntidade'.PHP_EOL);
	fwrite($fd, $MapeiaDtoParaEntidade);
	fclose ($fd);
}
