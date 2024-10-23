{if $partnerLogos|count}
  <style type="text/css">
    .pl-partner-logos {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .pl-partner-logo {
      height: 8rem;
      padding: 1rem 1.5rem;
      display: flex;
      justify-content: center;
      align-items: center;
    }
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
    @media (min-width: 768px) {
      .pl-partner-logos {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }
      .pl-partner-logo {
        padding: 2rem 3rem;
        height: 10rem;
      }
    }
    @media (min-width: 1280px) {
      .pl-partner-logos {
        grid-template-columns: repeat(4, minmax(0, 1fr));
      }
    }
    @media (min-width: 1536px) {
      .pl-partner-logos {
        grid-template-columns: repeat(5, minmax(0, 1fr));
      }
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

