# Component: `message_board`

A Mokuji component that provides a message board based on social media feeds.

Dependencies:

- Component: [`api_cache`](https://github.com/Tuxion/mokuji-api-cache) (depends on plugin [`codebird`](https://github.com/Tuxion/mokuji-plugin-codebird))
- Component: [`media`](https://github.com/Tuxion/tx.cms-media) (depends on plugin [`plupload`](https://github.com/Tuxion/tx.cms-plugin-plupload))
- Plugin: [`readability`](https://github.com/Mokuji/plugin-readability)

TODO:

1. Implement a javascript library to display the feeds.
1. Parse author information better: table with avatar, uri, name, id, etc.
1. Improve the `readability` plugin.
1. Cache message images.
1. Implement a cache cleanup for `api_cache` (since parameters change over time)
1. Implement an update delay for feeds.