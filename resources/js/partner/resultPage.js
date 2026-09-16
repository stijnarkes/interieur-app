const POLL_INTERVAL_MS = 5000;

const FACT_LABELS = {
  primary_style_match: (fact) => `Jullie hebben allebei ${fact.styleKey} als hoofdstijl.`,
  secondary_style_match: (fact) => `Jullie delen ook ${fact.styleKey} als invloed.`,
  primary_style_difference: (fact) => `Verschillende hoofdstijl: ${fact.initiatorStyleKey} bij de één, ${fact.partnerStyleKey} bij de ander.`,
  base_palette_color_match: () => "Jullie kozen (deels) dezelfde basiskleur.",
  accent_color_match: () => "Jullie kozen dezelfde accentkleur.",
  shared_option_selection: () => "Bij minstens één vraag kozen jullie precies hetzelfde.",
  shared_material_tags: (fact) => `Gedeelde materiaalvoorkeur: ${(fact.tags || []).join(", ")}.`,
};

function describeFact(fact) {
  const describe = FACT_LABELS[fact.type];
  return describe ? describe(fact) : null;
}

function renderSwatches(container, colors) {
  if (!Array.isArray(colors) || colors.length === 0) return;

  const row = document.createElement("div");
  row.className = "base-palette-swatch-row";
  colors.forEach((color) => {
    const swatch = document.createElement("div");
    swatch.className = "base-palette-swatch";
    const chip = document.createElement("span");
    chip.className = "base-palette-swatch-color";
    chip.style.backgroundColor = color.hex;
    const name = document.createElement("span");
    name.className = "base-palette-swatch-name";
    name.textContent = color.name || "";
    swatch.appendChild(chip);
    swatch.appendChild(name);
    row.appendChild(swatch);
  });
  container.appendChild(row);
}

/** GET /gezamenlijk/{accessToken} — sectie 4.D (wachten) / sectie 5 (resultaat) van het implementatieplan. */
function initResultPage(root) {
  const accessToken = root.dataset.accessToken;
  const ctaLabel = root.dataset.ctaLabel;
  const ctaUrl = root.dataset.ctaUrl;
  const mount = root.querySelector("#partnerResultMount");
  let pollTimer = null;

  function stopPolling() {
    if (pollTimer) {
      clearTimeout(pollTimer);
      pollTimer = null;
    }
  }

  function renderWaiting(role) {
    mount.innerHTML = "";
    const heading = document.createElement("h1");
    heading.textContent = "Jullie gezamenlijke woonstijl";
    mount.appendChild(heading);

    const body = document.createElement("p");
    body.className = "section-intro";
    body.textContent = role === "initiator"
      ? "We wachten nog op je partner. Zodra die de test heeft afgerond, verschijnt hier automatisch jullie gezamenlijke advies — deze pagina hoef je niet te verversen."
      : "Bedankt voor het afronden van je test! Zodra jullie allebei klaar zijn, verschijnt hier automatisch jullie gezamenlijke advies.";
    mount.appendChild(body);
  }

  function renderProcessing() {
    mount.innerHTML = "";
    const body = document.createElement("p");
    body.className = "section-intro";
    body.textContent = "Jullie advies wordt samengesteld, een moment geduld...";
    mount.appendChild(body);
  }

  function renderNotFound() {
    stopPolling();
    mount.innerHTML = "";
    const heading = document.createElement("h1");
    heading.textContent = "Deze link is niet (meer) geldig";
    mount.appendChild(heading);
  }

  function renderReady(data) {
    stopPolling();
    mount.innerHTML = "";

    const heading = document.createElement("h1");
    heading.textContent = "Jullie gezamenlijke woonstijl";
    mount.appendChild(heading);

    const intro = document.createElement("p");
    intro.className = "section-intro";
    const initiatorLabel = data.initiatorName || "Deelnemer 1";
    const partnerLabel = data.partnerName || "Deelnemer 2";
    intro.textContent = `${initiatorLabel}: ${data.initiatorStyle ?? "onbekend"} — ${partnerLabel}: ${data.partnerStyle ?? "onbekend"}`;
    mount.appendChild(intro);

    if ((data.initiatorPalette?.colors?.length || data.partnerPalette?.colors?.length)) {
      const paletteHeading = document.createElement("h3");
      paletteHeading.textContent = "Jullie basiskleuren";
      mount.appendChild(paletteHeading);
      renderSwatches(mount, data.initiatorPalette?.colors ?? []);
      renderSwatches(mount, data.partnerPalette?.colors ?? []);
    }

    if ((data.initiatorAccentColors?.length || data.partnerAccentColors?.length)) {
      const accentHeading = document.createElement("h3");
      accentHeading.textContent = "Jullie accentkleuren";
      mount.appendChild(accentHeading);
      renderSwatches(mount, data.initiatorAccentColors ?? []);
      renderSwatches(mount, data.partnerAccentColors ?? []);
    }

    const similarities = (data.facts?.similarities ?? []).map(describeFact).filter(Boolean);
    if (similarities.length > 0) {
      const simHeading = document.createElement("h3");
      simHeading.textContent = "Wat jullie delen";
      mount.appendChild(simHeading);
      const list = document.createElement("ul");
      list.className = "report-checklist";
      similarities.forEach((text) => {
        const li = document.createElement("li");
        li.textContent = text;
        list.appendChild(li);
      });
      mount.appendChild(list);
    }

    const differences = (data.facts?.differences ?? []).map(describeFact).filter(Boolean);
    if (differences.length > 0) {
      const diffHeading = document.createElement("h3");
      diffHeading.textContent = "Waarin jullie verschillen";
      mount.appendChild(diffHeading);
      const list = document.createElement("ul");
      list.className = "report-checklist";
      differences.forEach((text) => {
        const li = document.createElement("li");
        li.textContent = text;
        list.appendChild(li);
      });
      mount.appendChild(list);
    }

    if (data.suggestions?.title) {
      const adviceHeading = document.createElement("h3");
      adviceHeading.textContent = data.suggestions.title;
      mount.appendChild(adviceHeading);

      [data.suggestions.intro, data.suggestions.basisTip, data.suggestions.materialsTip, data.suggestions.accentTip]
        .filter(Boolean)
        .forEach((text) => {
          const p = document.createElement("p");
          p.className = "section-intro";
          p.textContent = text;
          mount.appendChild(p);
        });
    }

    if (ctaLabel && ctaUrl) {
      const actions = document.createElement("div");
      actions.className = "actions";
      const ctaLink = document.createElement("a");
      ctaLink.className = "btn btn-primary";
      ctaLink.href = ctaUrl;
      ctaLink.target = "_blank";
      ctaLink.rel = "noopener noreferrer";
      ctaLink.textContent = ctaLabel;
      actions.appendChild(ctaLink);
      mount.appendChild(actions);
    }
  }

  async function poll() {
    let data;
    try {
      const response = await fetch(`/api/partner-comparisons/${accessToken}`, {
        headers: { Accept: "application/json" },
        signal: AbortSignal.timeout(10000),
      });

      if (response.status === 404) {
        renderNotFound();
        return;
      }

      data = await response.json();
    } catch {
      pollTimer = setTimeout(poll, POLL_INTERVAL_MS);
      return;
    }

    if (data.status === "ready") {
      renderReady(data);
      return;
    }

    if (data.status === "processing") {
      renderProcessing();
    } else {
      renderWaiting(data.role);
    }

    pollTimer = setTimeout(poll, POLL_INTERVAL_MS);
  }

  poll();
}

export { initResultPage };
