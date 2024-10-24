{if $partnerLogos|count}
  <style type="text/css">
    .pl-partner-logo-img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }
    .pl-partner-logo-link {
      text-decoration: none;
      border: none;
      background: transparent;
    }
    .pl-partner-logo-link:focus-visible {
      outline: 2px solid currentColor;
    }
    .pl-partner-logos {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
    }
    .pl-partner-logo {
      flex: 0 0 16rem;
      max-height: 10rem;
      height: 10rem;
      max-width: 50%;
      width: 100%;
      padding: 2rem;
      display: grid;
      place-items: center;
    }
  </style>
  <div class="pl-partner-logos">
    {foreach from=$partnerLogos item="partnerLogo"}
      {assign var="name" value=$partnerLogo->getLocalizedName()}
      {if $name|substr:0:4 === 'http'}
        <a
          class="pl-partner-logo pl-partner-logo-link"
          href="{$name|trim|escape}"
          target="_blank"
        >
      {else}
        <div class="pl-partner-logo">
      {/if}
        <img
          class="pl-partner-logo-img"
          src="{url page="libraryFiles" op="downloadPublic" path=$partnerLogo->getId()}"
        >
      {if $name|substr:0:4 === 'http'}
        </a>
      {else}
        </div>
      {/if}
    {/foreach}
  </div>
{/if}

