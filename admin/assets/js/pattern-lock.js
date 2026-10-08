/**
 * Pattern Lock
 *
 * Locks the layout of the toolkit patterns (hithto/*) in the block editor.
 * Only loaded for users who can't edit the theme options.
 *
 * Users can still edit texts, images and links, but can't change styles
 * or add, remove and reorder the blocks inside a pattern.
 */
(function(wp) {
    'use strict';

    // Make sure we have the required WordPress objects
    if (typeof wp === 'undefined' || !wp.data || !wp.blocks) {
        return;
    }

    var PATTERN_PREFIX = 'hithto/';
    var STORE = 'core/block-editor';

    // Editing modes applied by this script, keyed by block clientId
    var appliedModes = {};
    var lastBlocks = null;
    var isApplying = false;

    /**
     * Check if a block holds content (text, image, link...).
     * Same rule as the editor uses for patterns, plus ACF blocks.
     */
    function isContentBlock(name) {
        if (name.indexOf('acf/') === 0) {
            return true;
        }

        var blockType = wp.blocks.getBlockType(name);
        if (!blockType) {
            return false;
        }

        if (blockType.supports && blockType.supports.contentRole) {
            return true;
        }

        var attributes = blockType.attributes || {};
        return Object.keys(attributes).some(function(key) {
            return attributes[key].role === 'content' || attributes[key].__experimentalRole === 'content';
        });
    }

    /**
     * Check if a block is the root of a toolkit pattern.
     */
    function isToolkitPattern(block) {
        var metadata = block.attributes && block.attributes.metadata;
        return !!(metadata && metadata.patternName && metadata.patternName.indexOf(PATTERN_PREFIX) === 0);
    }

    /**
     * Lock the inner blocks of a pattern: content blocks stay editable, the others are disabled.
     */
    function lockInnerBlocks(blocks, modes) {
        blocks.forEach(function(block) {
            modes[block.clientId] = isContentBlock(block.name) ? 'contentOnly' : 'disabled';
            lockInnerBlocks(block.innerBlocks, modes);
        });
    }

    /**
     * Find the toolkit patterns in the block tree and compute their editing modes.
     */
    function collectModes(blocks, modes) {
        blocks.forEach(function(block) {
            if (isToolkitPattern(block)) {
                // The pattern itself can still be moved or removed
                modes[block.clientId] = 'contentOnly';
                lockInnerBlocks(block.innerBlocks, modes);
            } else {
                collectModes(block.innerBlocks, modes);
            }
        });
    }

    function applyModes(dispatch, blocks) {
        var modes = {};
        collectModes(blocks, modes);

        Object.keys(modes).forEach(function(clientId) {
            if (appliedModes[clientId] !== modes[clientId]) {
                dispatch.setBlockEditingMode(clientId, modes[clientId]);
            }
        });

        // Release the blocks that are no longer part of a toolkit pattern
        Object.keys(appliedModes).forEach(function(clientId) {
            if (!modes[clientId]) {
                dispatch.unsetBlockEditingMode(clientId);
            }
        });

        appliedModes = modes;
    }

    /**
     * Lock the structure of the pattern containers (move, insert, remove),
     * even when the user enters the "Edit pattern" mode.
     * The inner blocks component can reset these settings, so they are checked on every change.
     */
    function applyTemplateLock(select, dispatch) {
        Object.keys(appliedModes).forEach(function(clientId) {
            var settings = select.getBlockListSettings(clientId);
            if (settings && settings.templateLock !== 'all') {
                dispatch.updateBlockListSettings(clientId, Object.assign({}, settings, { templateLock: 'all' }));
            }
        });
    }

    function lockPatterns() {
        if (isApplying) {
            return;
        }
        isApplying = true;

        var select = wp.data.select(STORE);
        var dispatch = wp.data.dispatch(STORE);
        var blocks = select.getBlocks();

        // Only recompute the editing modes when the block tree changes
        if (blocks !== lastBlocks) {
            lastBlocks = blocks;
            applyModes(dispatch, blocks);
        }

        applyTemplateLock(select, dispatch);

        isApplying = false;
    }

    wp.data.subscribe(lockPatterns, STORE);
})(window.wp);
