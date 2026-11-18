<template>
	<!-- a figure in the block's list: its number and unit, its label -->
	<div class="pwPreview" @dblclick="open">
		<div class="item">
			<div v-if="content.icon" class="pwIcon" v-html="content.icon"></div>
			<div class="pwNumber">{{ content.number || '–' }}<span v-if="content.unit" class="pwUnit">{{ content.unit }}</span></div>
			<div v-if="content.label" class="pwLabel">{{ content.label }}</div>
		</div>
	</div>
</template>

<style scoped>
div.item {
	display: flex;
	align-items: baseline;
	gap: var(--spacing-3);
	padding: var(--spacing-2);
	font-size: var(--text-sm);
}
div.pwIcon {
	align-self: center;
	width: 24px;
	height: 24px;
	opacity: 0.5;
}
div.pwIcon :deep(svg) {
	width: 24px;
	height: 24px;
	fill: currentColor;
}
div.pwNumber {
	font-size: var(--text-lg);
	font-weight: var(--font-bold);
	white-space: nowrap;
}
span.pwUnit {
	font-size: 0.7em;
	margin-left: 0.08em;
}
div.pwLabel {
	opacity: 0.8;
}
</style>
