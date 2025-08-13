const { defineConfig } = require("cypress");
const { phpVersion, core } = require('./.wp-env.json');
const wpVersion = /[^/]*$/.exec(core)[0];

module.exports = defineConfig({
  e2e: {
    setupNodeEvents(on, config) {
      // node event listeners if needed
    },
    env: {
      wpVersion,
      phpVersion,
      pluginId: 'bluehost',
      appId: 'wppbh',
       host: 'bh',
      pluginSlug: 'bluehost-wordpress-plugin',

      SC_shared_source: process.env.SC_SHARED_SOURCE,
      WP_USERNAME: process.env.WP_USERNAME,
      WP_PASSWORD: process.env.WP_PASSWORD,
      SC_cloud_destin: process.env.SC_CLOUD_DESTIN,
      DEST_USERNAME: process.env.DEST_USERNAME,
      DEST_PASSWORD: process.env.DEST_PASSWORD,

      CS_cloud_source: process.env.CS_CLOUD_SOURCE,
      CS_shared_destin: process.env.CS_SHARED_DESTIN,

      SS_shared_source: process.env.SS_SHARED_SOURCE,
      SS_shared_destin: process.env.SS_SHARED_DESTIN,

      CC_cloud_source: process.env.CC_CLOUD_SOURCE,
      CC_cloud_destin: process.env.CC_CLOUD_DESTIN,
    }
  }
});
