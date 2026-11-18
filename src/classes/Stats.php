<?php

/**
 * The stats block: a figure read for counting up – "+43", "-16", "1,5",
 * "12.000" (German or English separators). Anything else ("24/7") is
 * shown as it is.
 */
class pwStats
{
	/** [value, decimals, decimal separator, thousands separator, sign] or null */
	public static function parse(string $number): ?array
	{
		$number = trim($number);
		if (!preg_match('/^([+\-−–]?)\s*(\d{1,3}(?:[.,\s]\d{3})+|\d+)(?:([.,])(\d+))?$/u', $number, $m)) return null;
		[$all, $sign, $int] = $m;
		$sep  = $m[3] ?? '';
		$frac = $m[4] ?? '';
		// "12.000" (three digits after one separator, no decimals): thousands
		$thousands = '';
		if (preg_match('/^\d{1,3}([.,\s])\d{3}/', $int, $t)) $thousands = $t[1];
		$plain = str_replace(['.', ',', ' '], '', $int);
		if ($sep !== '' && $thousands === '' && strlen($frac) === 3 && $sep !== '') {
			// "1.500" or "1,500": a thousands separator, not decimals
			$thousands = $sep;
			$plain .= $frac;
			$frac = '';
			$sep = '';
		}
		$value = (float) ($plain . ($frac !== '' ? '.' . $frac : ''));
		return [$value, strlen($frac), $sep !== '' ? $sep : ($thousands === '.' ? ',' : '.'), $thousands, $sign];
	}

	/**
	 * The maximum as shown: with the number's decimal separator ("1,5"
	 * → "4,5"), else the language's (German: a comma)
	 */
	public static function formatMax(string $max, string $number): string
	{
		$max = str_replace(',', '.', $max);
		if (str_contains($max, '.')) $max = rtrim(rtrim($max, '0'), '.');
		$parsed = static::parse($number);
		$dec = $parsed && ($parsed[1] > 0 || $parsed[3] !== '') ? $parsed[2] : (kirby()->language()?->code() === 'de' ? ',' : '.');
		return str_replace('.', $dec, $max);
	}

	/** The number's value (0 when it is none: "24/7") */
	public static function value(string $number): float
	{
		$parsed = static::parse($number);
		if (!$parsed) return 0;
		return $parsed[4] === '' || $parsed[4] === '+' ? $parsed[0] : -$parsed[0];
	}

	/**
	 * The script of the animation (once a page): each figure set to its
	 * start when the page loads, played when it comes into view – the
	 * numbers counted up, the bars, rings, columns and the pie grown by CSS
	 * (data-play: wait → run), one after the other (the stagger)
	 */
	public static function script(): string
	{
		static $done = false;
		if ($done) return '';
		$done = true;
		return <<<'JS'
<script>
(function () {
	var run = function () {
		var lists = document.querySelectorAll('[data-block="items"][data-animate]:not([data-play])');
		if (!lists.length || !('IntersectionObserver' in window)) return;
		if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
		var seconds = function (el, name, start) {
			var v = parseFloat(getComputedStyle(el).getPropertyValue(name));
			return (isNaN(v) ? start : v) * 1000;
		};
		var format = function (el, v) {
			var d = +el.dataset.decimals || 0, s = v.toFixed(d).split('.'), t = el.dataset.thousands || '';
			if (t) s[0] = s[0].replace(/\B(?=(\d{3})+(?!\d))/g, t);
			return (el.dataset.sign || '') + s[0] + (d ? (el.dataset.dec || ',') + s[1] : '');
		};
		var count = function (el, ms, delay) {
			var to = +el.dataset.countTo, final = el.dataset.final, start = null;
			var step = function (now) {
				if (start === null) start = now + delay;
				var p = Math.max(0, Math.min(1, (now - start) / ms)), e = 1 - Math.pow(1 - p, 3);
				el.textContent = p < 1 ? format(el, to * e) : final;
				if (p < 1) requestAnimationFrame(step);
			};
			requestAnimationFrame(step);
		};
		var play = function (list) {
			var ms = seconds(list, '--pwstats-item-count-duration', 1.5), gap = seconds(list, '--pwstats-item-stagger', 0.15);
			list.querySelectorAll('[data-count-to]').forEach(function (el) {
				var item = el.closest('[data-block="item"]');
				count(el, ms, (item ? +item.style.getPropertyValue('--pwstats-i') || 0 : 0) * gap);
			});
			// (the start drawn first, then the way to the end)
			requestAnimationFrame(function () { requestAnimationFrame(function () { list.dataset.play = 'run'; }); });
		};
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				io.unobserve(entry.target);
				play(entry.target);
			});
		}, { threshold: 0.3 });
		lists.forEach(function (list) {
			list.dataset.play = 'wait';
			list.querySelectorAll('[data-count-to]').forEach(function (el) {
				el.dataset.final = el.textContent;
				el.textContent = format(el, 0);
			});
			io.observe(list);
		});
	};
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
})();
</script>
JS;
	}
}
