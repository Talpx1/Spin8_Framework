<?php

namespace Spin8\WP\Settings\Enums;

enum SettingsGroups: string {
    case GENERAL = 'general';
    case DISCUSSION = 'discussion';
    case MEDIA = 'media';
    case READING = 'reading';
    case WRITING = 'writing';
    case OPTIONS = 'options';
}
