<?php

// Config
$config   = pwConfig::load('pwstats');
$settings = $config['content'];
$defaults = $config['defaults'];

// the variant's own look: surface, border and dividers each on or off
$theme  = $block->theme()->value();
$suffix = in_array($theme, ['variant', 'variant2', 'variant3'], true) ? '-' . $theme : '';
$onOff  = fn (string $key, string $start) => (($defaults[$key . $suffix] ?? $defaults[$key] ?? $start) === 'off') ? 'off' : 'on';
$count  = ($defaults['item-count'] ?? 'on') !== 'off';

// Custom Background
pwSnippet::customCss($block);

// Section + Grid open
echo pwSnippet::sectionOpen('stats', $block, $settings);
echo pwSnippet::gridOpen($block);

// Tagline
if (!empty($settings['tagline'])):
	snippet('tagline', ['content' => $block]);
endif;

// Heading
if (!empty($settings['heading'])):
	snippet('heading', ['content' => $block]);
endif;

// Editor
if (!empty($settings['editor'])):
	snippet('editor', ['content' => $block]);
endif;

// The figures (those with neither a number nor a label left out: an item
// added and closed again)
$view  = $block->content()->get('statsview')->or($defaults['stats-view'] ?? 'numbers')->value();
$view  = in_array($view, ['numbers', 'bars', 'rings', 'columns', 'pie'], true) ? $view : 'numbers';
$items = [];
foreach ($block->blocks()->toBlocks() as $item):
	// (through the content: "label" is also a method of a block)
	$number = trim((string) $item->content()->get('number')->value());
	$label  = trim((string) $item->content()->get('label')->value());
	if ($number === '' && $label === '') continue;
	$maxRaw = trim((string) $item->content()->get('max')->value());
	$max    = (float) str_replace(',', '.', $maxRaw);
	$items[] = [
		'number' => $number,
		'unit'   => trim((string) $item->content()->get('unit')->value()),
		'label'  => $label,
		'icon'   => $item->icon()->isNotEmpty() ? $item->icon()->value() : '',
		'value'  => max(0, pwStats::value($number)),
		'max'    => $max > 0 ? $max : 100,
		// shown next to the number (bars, rings): "24 / 60 cm"
		'maxText' => $max > 0 && in_array($view, ['bars', 'rings'], true) ? pwStats::formatMax($maxRaw, $number) : '',
	];
endforeach;

if ($items):

	// the share of each: of its maximum (bars, rings), of the largest
	// (columns), of all together (pie)
	$largest = max(array_column($items, 'value')) ?: 1;
	$sum     = array_sum(array_column($items, 'value')) ?: 1;
	foreach ($items as $i => $item):
		$share = match ($view) {
			'columns' => $item['value'] / $largest * 100,
			'pie'     => $item['value'] / $sum * 100,
			default   => $item['value'] / $item['max'] * 100,
		};
		$items[$i]['share'] = round(min(100, max(0, $share)), 2);
	endforeach;

	// the number (counted up, when it reads as one), its unit smaller
	$numberHtml = function (array $item) use ($count) {
		$parsed = $count ? pwStats::parse($item['number']) : null;
		$html = '<p data-field="number">';
		if ($parsed):
			[$value, $decimals, $dec, $thousands, $sign] = $parsed;
			$html .= '<span data-count-to="'.$value.'" data-decimals="'.$decimals.'" data-dec="'.esc($dec, 'attr').'" data-thousands="'.esc($thousands, 'attr').'" data-sign="'.esc($sign, 'attr').'">'.esc($item['number']).'</span>';
		else:
			$html .= '<span>'.esc($item['number']).'</span>';
		endif;
		if ($item['maxText'] !== ''):
			// (the unit then after the maximum, as small as it)
			$html .= '<span data-field="max">/ '.esc($item['maxText']).($item['unit'] !== '' ? ' '.esc($item['unit']) : '').'</span>';
		elseif ($item['unit'] !== ''):
			$html .= '<span data-field="unit">'.esc($item['unit']).'</span>';
		endif;
		return $html.'</p>';
	};
	$labelHtml = fn (array $item) => $item['label'] !== '' ? '<p data-field="label">'.esc($item['label']).'</p>' : '';

	echo '<div data-block="items" data-view="'.$view.'"';
	if (in_array($view, ['numbers', 'rings'], true)):
		foreach (['sm', 'md', 'lg', 'xl'] as $bp):
			echo ' data-columns-'.$bp.'="'.$block->content()->get('columns'.$bp)->or($defaults['columns-'.$bp] ?? 2)->value().'"';
		endforeach;
	endif;
	echo ' data-align="'.(($defaults['item-align'] ?? 'left') === 'center' ? 'center' : 'left').'"';
	echo ' data-surface="'.$onOff('item-surface', 'on').'"';
	echo ' data-border="'.$onOff('item-border', 'off').'"';
	echo ' data-dividers="'.$onOff('item-dividers', 'off').'"';
	echo ' data-shadow="'.esc($defaults['item-shadow'] ?? 'none', 'attr').'"';
	// the glow (Project Wizard › Blocks, per variant; the custom variant: the default's)
	$glowSuffix = in_array($theme, ['variant', 'variant2', 'variant3'], true) ? '-' . $theme : '';
	$glowValue  = fn (string $key, array $allowed) => in_array($v = $defaults[$key . $glowSuffix] ?? $defaults[$key] ?? $allowed[0], $allowed, true) ? $v : $allowed[0];
	$glow       = $glowValue('item-glow', ['off', 'always', 'hover']);
	if ($glow !== 'off'):
		echo ' data-glow="'.$glow.'"';
		echo ' data-glow-color="'.$glowValue('item-glow-color', ['cyan', 'pink', 'violet', 'amber', 'custom']).'"';
		if ($glowValue('item-glow-direction', ['around', 'down']) === 'down') echo ' data-glow-direction="down"';
		if ($glowValue('item-glow-strength', ['subtle', 'strong']) === 'strong') echo ' data-glow-strength="strong"';
	endif;
	echo ' data-motion="'.(($defaults['item-motion'] ?? 'soft') === 'spring' ? 'spring' : 'soft').'"';
	echo ' data-bar-corners="'.(($defaults['item-bar-corners'] ?? 'round') === 'square' ? 'square' : 'round').'"';
	if ($view === 'columns') echo ' style="--pwstats-count: '.count($items).'"';
	if ($count) echo ' data-animate';
	echo '>'."\n";

	// pie: the chart (its pieces in the chart colours, one after the other)
	if ($view === 'pie'):
		$stops = [];
		$from = 0;
		foreach ($items as $i => $item):
			$to = $i === count($items) - 1 ? 100 : $from + $item['share'];
			$stops[] = 'var(--pw-stats-chart-'.($i % 6 + 1).') '.$from.'% '.$to.'%';
			$from = $to;
		endforeach;
		echo '<div data-field="pie" data-pie="'.(($defaults['item-pie'] ?? 'donut') === 'full' ? 'full' : 'donut').'" style="background: conic-gradient('.implode(', ', $stops).')" aria-hidden="true"></div>'."\n";
		echo '<div data-field="legend">'."\n";
	endif;

	foreach ($items as $i => $item):
		echo '<div data-block="item" style="--pwstats-i: '.$i.'; --pwstats-share: '.$item['share'].'%">'."\n";

		switch ($view):
			case 'bars':
				echo '<div data-field="bar-head">'.$labelHtml($item).$numberHtml($item).'</div>'."\n";
				echo '<div data-field="track" aria-hidden="true"><div data-field="fill"></div></div>'."\n";
				break;
			case 'rings':
				echo '<div data-field="ring-box"><div data-field="ring" aria-hidden="true"></div>'.$numberHtml($item).'</div>'."\n";
				echo $labelHtml($item)."\n";
				break;
			case 'columns':
				echo '<div data-field="column-area">'.$numberHtml($item).'<div data-field="column" aria-hidden="true"></div></div>'."\n";
				echo $labelHtml($item)."\n";
				break;
			case 'pie':
				echo '<span data-field="swatch" style="background-color: var(--pw-stats-chart-'.($i % 6 + 1).')" aria-hidden="true"></span>';
				echo $numberHtml($item).$labelHtml($item)."\n";
				break;
			default:
				if ($item['icon'] !== ''):
					echo '<div data-field="icon" aria-hidden="true">'.$item['icon'].'</div>'."\n";
				endif;
				echo $numberHtml($item)."\n".$labelHtml($item)."\n";
		endswitch;

		echo '</div>'."\n"; // End Item
	endforeach;

	if ($view === 'pie') echo '</div>'."\n"; // End Legend
	echo '</div>'."\n"; // End Items

	if ($count) echo pwStats::script();
endif;

// Close
echo pwSnippet::gridClose();
echo pwSnippet::sectionClose();
