# Component: `message_board`

A Mokuji component that provides a message board based on social media feeds.

Dependencies:

- Component: `api_cache` (depends on plugin `codebird`)
- Component: `media` (depends on plugin `plupload`)
- Plugin: `readability`

TODO:

1. Finish `FeedReader::update` using a different class.
2. Parse author information better: table with avatar, uri, name, id, etc.
3. Cache message images.
4. Cache message webpages and clean with readability plugin.
5. Implement a cache cleanup for `api_cache` (since parameters change over time)
6. Implement an update delay for feeds.
7. Implement a REST api for the feeds.
8. Implement a javascript library to display the feeds.