<%-- <% require themedCSS('hero') %> --%>
<% require css('antlion/element-hero:client/css/hero.css') %>
<% cached $CacheKey %>
<div class="hero-section hero-{$Theme} hero-h-{$Height} <% if $ExtraClass %> $ExtraClass<% end_if %>"<% if $HeroStyle %> style="{$HeroStyle}"<% end_if %>>

  <% if $HasOverlay %>
    <div class="hero-overlay" style="{$OverlayStyle}"></div>
  <% end_if %>

  <div class="hero-inner {$HorizontalAlignClass} {$VerticalAlignClass}">
    <div class="{$PaddingClass}">
      <% if $Title && $ShowTitle %>
        <% with $HeadingTag %>
          <{$Me} class="hero-title">$Up.Title.XML</{$Me}>
        <% end_with %>
      <% end_if %>

      <% if $Content %>$Content<% end_if %>

      <% if $Links.Exists %>
        <div class="button-group stacked-for-small {$HorizontalAlignClass}">
          <% loop $Links %>
            <a class="button $CssClass" href="$URL"<% if $OpenInNew %> target="_blank" rel="noopener noreferrer"<% end_if %>>$Title.XML</a>
          <% end_loop %>
        </div>
      <% end_if %>
    </div>
  </div>
</div>
<% end_cached %>
