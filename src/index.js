// Blocks
import pwstats from "@/blocks/index.vue";
import pwstatsitem from "@/blocks/item.vue";

// Render
panel.plugin("kirbydesk/kirbyblock-stats", {
	blocks: {
		pwstats:     pwstats,
		pwstatsitem: pwstatsitem,
	}
});
