<?php
namespace Antlion\ElementHero\Elements;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ToggleCompositeField;
use TractorCow\Colorpicker\Forms\ColorField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\LinkField\Models\Link;

class ElementHero extends BaseElement
{
    private static array $db = [
        'Title'          => 'Varchar(255)',     // Headline
        'Content'        => 'HTMLText',
        'Theme'          => 'Enum("light,dark","dark")',
        'OverlayOpacity' => 'Int',
        'Height'         => 'Enum("auto,short,medium,tall,full","tall")',
        'VerticalAlign'  => 'Enum("top,middle,bottom","middle")',
        'HorizontalAlign'=> 'Enum("left,center,right","left")',
        'Padding'             => 'Enum("none,20px,40px,60px","none")',
        'BackgroundColor'     => 'Varchar(20)',
        'BackgroundAttachment'=> "Enum('scroll,fixed,local','scroll')",
        'OverlayColor'        => 'Varchar(20)',
    ];

    private static array $has_one = [
        'BackgroundImage' => Image::class,
    ];

    private static array $has_many = [
        'Links' => Link::class . '.Owner',
    ];

    private static array $defaults = [
        'VerticalAlign'   => 'middle',
        'HorizontalAlign' => 'left',
        'Padding'         => '20px',
        'Theme'           => 'dark',
        'Height'          => 'tall',
    ];

    private static array $owns = [
        'BackgroundImage',
        'Links',
    ];

    private static string $icon       = 'font-icon-block-banner';
    private static string $table_name = 'ElementHero';

    public function getType(): string
    {
        return 'Hero';
    }

    public function inlineEditable(): bool
    {
        return false;
    }

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Theme',
            'Height',
            'VerticalAlign',
            'HorizontalAlign',
            'Padding',
            'OverlayOpacity',
            'Links',
            'BackgroundImage',
            'BackgroundColor',
            'BackgroundAttachment',
            'OverlayColor',
        ]);

        $fields->addFieldsToTab('Root.Main', [
            TextField::create('Title', 'Headline'),
            HTMLEditorField::create('Content', 'Hero Content'),

            ToggleCompositeField::create('LayoutSettings', 'Layout', [
                DropdownField::create('Height', 'Height', [
                    'auto'   => 'Auto',
                    'short'  => 'Short',
                    'medium' => 'Medium',
                    'tall'   => 'Tall',
                    'full'   => 'Full viewport',
                ]),
                DropdownField::create('HorizontalAlign', 'Horizontal Alignment', [
                    'left'   => 'Left',
                    'center' => 'Center',
                    'right'  => 'Right',
                ]),
                DropdownField::create('VerticalAlign', 'Vertical Alignment', [
                    'top'    => 'Top',
                    'middle' => 'Middle',
                    'bottom' => 'Bottom',
                ]),
                DropdownField::create('Padding', 'Content Padding', [
                    'none' => 'None',
                    '20px' => 'Small (20px)',
                    '40px' => 'Medium (40px)',
                    '60px' => 'Large (60px)',
                ]),
            ])->setStartClosed(true),

            ToggleCompositeField::create('BackgroundSettings', 'Background', [
                UploadField::create('BackgroundImage', 'Background Image')
                    ->setFolderName('uploads/elements/hero-slides')
                    ->setAllowedFileCategories('image/supported'),
                ColorField::create('BackgroundColor', 'Background Color'),
                DropdownField::create('BackgroundAttachment', 'Background Attachment', [
                    'scroll' => 'Scroll (default)',
                    'fixed'  => 'Fixed (parallax)',
                    'local'  => 'Local',
                ]),
            ])->setStartClosed(true),

            ToggleCompositeField::create('OverlayThemeSettings', 'Overlay & Theme', [
                DropdownField::create('Theme', 'Text Theme', [
                    'light' => 'Light (dark text)',
                    'dark'  => 'Dark (white text)',
                ])->setDescription('Sets the default text color for content over the background'),
                ColorField::create('OverlayColor', 'Overlay Color'),
                NumericField::create('OverlayOpacity', 'Overlay Opacity (0–100)')
                    ->setDescription('0 = none, 100 = fully opaque. Requires Overlay Color to be set for a custom color.'),
            ])->setStartClosed(true),

            MultiLinkField::create('Links', 'Button Links'),
        ]);

        return $fields;
    }

    // 0–100 int -> 0–1 float string
    public function OverlayOpacityCss(): string
    {
        $pct = max(0, min(100, (int) $this->OverlayOpacity));
        return (string) round($pct / 100, 2);
    }

    public function HasOverlay(): bool
    {
        return (int) $this->OverlayOpacity > 0;
    }

    // Inline style string for the outer hero wrapper
    public function HeroStyle(): string
    {
        $parts = [];

        if ($this->BackgroundImageID && $this->BackgroundImage()->exists()) {
            $parts[] = "background-image: url('" . $this->BackgroundImage()->URL . "')";
        }

        if ($rgb = $this->BackgroundRGBA()) {
            $parts[] = 'background-color: ' . $rgb;
        }

        if ($this->BackgroundAttachment && $this->BackgroundAttachment !== 'scroll') {
            $parts[] = 'background-attachment: ' . $this->BackgroundAttachment;
        }

        return $parts ? implode('; ', $parts) . ';' : '';
    }

    // Inline style string for the overlay div
    public function OverlayStyle(): string
    {
        if ($rgba = $this->OverlayRGBA()) {
            // rgba already carries the alpha, so reset opacity to 1
            return 'background-color: ' . $rgba . '; opacity: 1;';
        }
        // No color set — use opacity only; CSS var provides the theme default color
        return '--hero-overlay: ' . $this->OverlayOpacityCss() . ';';
    }

    public function BackgroundRGBA(): ?string
    {
        $hex = (string) $this->BackgroundColor;
        if (!$hex) {
            return null;
        }

        $rgb = $this->hexToRgb($hex);
        return $rgb ? sprintf('rgb(%d,%d,%d)', $rgb[0], $rgb[1], $rgb[2]) : null;
    }

    public function OverlayRGBA(): ?string
    {
        $hex = (string) $this->OverlayColor;
        $opacity = (float) $this->OverlayOpacityCss();

        if (!$hex || $opacity <= 0) {
            return null;
        }

        $rgb = $this->hexToRgb($hex);
        return $rgb ? sprintf('rgba(%d,%d,%d,%.2f)', $rgb[0], $rgb[1], $rgb[2], $opacity) : null;
    }

    private function hexToRgb(string $hex): ?array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = "{$hex[0]}{$hex[0]}{$hex[1]}{$hex[1]}{$hex[2]}{$hex[2]}";
        }

        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    public function HorizontalAlignClass(): string
    {
        return match ($this->HorizontalAlign) {
            'center' => 'align-center text-center',
            'right'  => 'align-right text-right',
            'left'   => 'align-left text-left',
            default  => '',
        };
    }

    public function VerticalAlignClass(): string
    {
        return match ($this->VerticalAlign) {
            'top'    => 'align-self-top',
            'middle' => 'align-self-middle',
            'bottom' => 'align-self-bottom',
            default  => '',
        };
    }

    public function PaddingClass(): string
    {
        return match ($this->Padding) {
            'none' => '',
            '20px' => 'p-20',
            '40px' => 'p-40',
            '60px' => 'p-60',
            default => 'p-20',
        };
    }
}
