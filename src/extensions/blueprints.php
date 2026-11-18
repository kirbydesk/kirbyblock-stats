<?php

$columnsField = fn(string $breakpoint, $default) => [
	'extends' => 'pagewizard/fields/columns',
	'default' => $default,
	'label'   => 'pw.field.columns.' . $breakpoint,
];

return [
	'blocks/pwstats' => pwBlueprint::main('pwstats', function ($cfg) use ($columnsField) {
		$defaults = $cfg['defaults'];
		return [
			'name' => 'kirbyblock-stats.name',
			'icon' => 'chart',
			'contentFields' => array_merge(
				pwBlueprint::stdContent($cfg, ['tagline', 'heading', 'editor']),
				[
					'blocks' => [
						'extends'   => 'pagewizard/fields/blocks',
						'label'     => 'kirbyblock-stats.items',
						'fieldsets' => ['pwstatsitem'],
					],
				]
			),
			'layoutExtras' => [
				// numbers, bars, rings, columns or a pie (its look: Project Wizard →
				// Stats › Design)
				'statsView' => [
					'label'    => 'kirbyblock-stats.view',
					'type'     => 'toggles',
					'default'  => $defaults['stats-view'] ?? 'numbers',
					'required' => true,
					'options'  => array_map(fn ($view) => ['value' => $view, 'text' => ['*' => 'kirbyblock-stats.view.' . $view]], ['numbers', 'bars', 'rings', 'columns', 'pie']),
				],
				// (bars, columns and the pie: one chart, no columns)
				'headlineColumns' => ['extends' => 'pagewizard/headlines/columns', 'unless' => ['statsView' => ['bars', 'columns', 'pie']]],
				'columnsSm'       => $columnsField('sm', $defaults['columns-sm']),
				'columnsMd'       => $columnsField('md', $defaults['columns-md']),
				'columnsLg'       => $columnsField('lg', $defaults['columns-lg']),
				'columnsXl'       => $columnsField('xl', $defaults['columns-xl']),
			],
		];
	}),

	// one figure: its number, unit and label, an icon (optional)
	'blocks/pwstatsitem' => __DIR__ . '/../blueprints/item.yml',
];
