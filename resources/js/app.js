import './bootstrap';

const form = document.getElementById("wizardForm");
const stepCards = Array.from(document.querySelectorAll(".step-card[data-step]"));
const stepIndicators = Array.from(document.querySelectorAll("[data-step-indicator]"));

const EXPECTED_TOTAL_TIME = 120000;
const PROGRESS_UPDATE_INTERVAL = 400;
const STATUS_MESSAGE_INTERVAL = 4500;
const LATE_STAGE_MESSAGE_DELAY = 30000;
const PHASE_PROGRESS_LIMITS = [20, 45, 80, 96];

const PHASES = [
  { label: "1/4 We analyseren jouw woonstijl", startMs: 0, endMs: 15000, start: 0, end: PHASE_PROGRESS_LIMITS[0], easing: "easeOutQuad" },
  { label: "2/4 We maken jouw moodboard", startMs: 15000, endMs: 55000, start: PHASE_PROGRESS_LIMITS[0], end: PHASE_PROGRESS_LIMITS[1], easing: "easeInOutSine" },
  { label: "3/4 We visualiseren jouw ruimte", startMs: 55000, endMs: 95000, start: PHASE_PROGRESS_LIMITS[1], end: PHASE_PROGRESS_LIMITS[2], easing: "easeInOutCubic" },
  { label: "4/4 We ronden jouw advies af", startMs: 95000, endMs: EXPECTED_TOTAL_TIME, start: PHASE_PROGRESS_LIMITS[2], end: PHASE_PROGRESS_LIMITS[3], easing: "easeOutQuart" }
];

const ROTATING_STATUS_MESSAGES = [
  "We analyseren jouw stijlvoorkeuren",
  "We stellen je moodboard samen",
  "We zoeken passende materialen en kleuren",
  "We maken een inspiratiebeeld van jouw ruimte",
  "We werken jouw interieuradvies af"
];

const elements = {
  style: document.getElementById("style"),
  moodWords: document.getElementById("moodWords"),
  colors: document.getElementById("colors"),
  roomPhoto: document.getElementById("roomPhoto"),
  name: document.getElementById("name"),
  email: document.getElementById("email"),
  marketingOptIn: document.getElementById("marketingOptIn"),
  styleError: document.getElementById("styleError"),
  fileError: document.getElementById("fileError"),
  emailError: document.getElementById("emailError"),
  fileMeta: document.getElementById("fileMeta"),
  sendStatus: document.getElementById("sendStatus"),
  results: document.getElementById("results"),
  styleLabel: document.getElementById("styleLabel"),
  styleEditorialIntro: document.getElementById("styleEditorialIntro"),
  paletteIntro: document.getElementById("paletteIntro"),
  materialsIntro: document.getElementById("materialsIntro"),
  layoutIntro: document.getElementById("layoutIntro"),
  productIntro: document.getElementById("productIntro"),
  adviceBullets: document.getElementById("adviceBullets"),
  palette: document.getElementById("palette"),
  layoutTips: document.getElementById("layoutTips"),
  materials: document.getElementById("materials"),
  productIdeas: document.getElementById("productIdeas"),
  moodboardImage: document.getElementById("moodboardImage"),
  roomPreviewImage: document.getElementById("roomPreviewImage"),
  sendBtn: document.getElementById("sendBtn"),
  generateBtn: document.getElementById("generateBtn"),
  loadingState: document.getElementById("loadingState"),
  loadingStep: document.getElementById("loadingStep"),
  loadingProgressBar: document.getElementById("loadingProgressBar"),
  loadingPercent: document.getElementById("loadingPercent"),
  loadingMessage: document.getElementById("loadingMessage"),
  loadingHelperLine: null,
  loadingLateMessage: null
};

const styleCards = Array.from(document.querySelectorAll("[data-style-card]"));
const moodChips = Array.from(document.querySelectorAll("[data-mood]"));
const colorSwatches = Array.from(document.querySelectorAll("[data-color-name]"));

const state = {
  step: 1,
  generatedResultId: "",
  selectedFile: null,
  selectedMoodWords: new Set(),
  selectedColors: new Set(),
  isGenerating: false,
  progressValue: 0,
  progressTimer: null,
  statusTimer: null,
  progressStartedAt: 0,
  statusIndex: 0,
  lateMessageShown: false
};

const MAX_FILE_SIZE = 5 * 1024 * 1024;

function ensureLoadingTextNodes() {
  const helper = document.createElement("p");
  helper.className = "hint";
  helper.id = "loadingHelperLine";
  helper.textContent = "Even geduld, we stellen jouw persoonlijke interieuradvies samen.";

  const late = document.createElement("p");
  late.className = "hint loading-late";
  late.id = "loadingLateMessage";
  late.textContent = "Je interieuradvies wordt bijna afgerond...";
  late.hidden = true;

  if (elements.loadingMessage && elements.loadingMessage.parentElement) {
    elements.loadingMessage.insertAdjacentElement("afterend", helper);
    helper.insertAdjacentElement("afterend", late);
  }

  elements.loadingHelperLine = helper;
  elements.loadingLateMessage = late;
}

function parseCommaInput(value) {
  return value
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);
}

function updateStepper(currentStep) {
  stepIndicators.forEach((item) => {
    const step = Number(item.dataset.stepIndicator);
    item.classList.toggle("is-active", step === currentStep);
    item.classList.toggle("is-done", step < currentStep);
    item.classList.toggle("is-pending", step > currentStep);

    const base = item.textContent.replace(" ✓", "").trim();
    item.textContent = step < currentStep ? base + " ✓" : base;
  });
}

// Header alleen zichtbaar op stap 1 (start) en resultaatpagina, verborgen op stappen 2 en 3.
function updateHeader(stepNumber) {
  document.getElementById("mainHeader").hidden = stepNumber !== 1;
}

function showStep(stepNumber) {
  state.step = stepNumber;
  stepCards.forEach((card) => {
    const isCurrent = Number(card.dataset.step) === stepNumber;
    card.hidden = !isCurrent;
  });
  updateStepper(stepNumber);
  updateHeader(stepNumber);
}

function clearErrors() {
  elements.styleError.textContent = "";
  elements.fileError.textContent = "";
  elements.emailError.textContent = "";
  elements.sendStatus.textContent = "";
}

function syncStyleCards() {
  styleCards.forEach((card) => {
    const isSelected = card.dataset.styleCard === elements.style.value;
    card.classList.toggle("is-selected", isSelected);
    card.setAttribute("aria-pressed", String(isSelected));
  });
}

function syncMoodChipsFromInput() {
  state.selectedMoodWords = new Set(parseCommaInput(elements.moodWords.value.toLowerCase()));
  moodChips.forEach((chip) => {
    const selected = state.selectedMoodWords.has(chip.dataset.mood);
    chip.classList.toggle("is-selected", selected);
  });
}

function syncColorSwatchesFromInput() {
  state.selectedColors = new Set(parseCommaInput(elements.colors.value.toLowerCase()));
  colorSwatches.forEach((swatch) => {
    const selected = state.selectedColors.has(swatch.dataset.colorName);
    swatch.classList.toggle("is-selected", selected);
  });
}

function updateInputFromSet(input, set) {
  input.value = Array.from(set).join(", ");
}

function validateStep1() {
  elements.styleError.textContent = "";
  if (!elements.style.value) {
    elements.styleError.textContent = "Kies een woonstijl om door te gaan.";
    return false;
  }
  return true;
}

function validateFile(file) {
  elements.fileError.textContent = "";
  if (!file) {
    elements.fileMeta.textContent = "Geen bestand geselecteerd.";
    return true;
  }

  const allowedTypes = ["image/jpeg", "image/png"];
  if (!allowedTypes.includes(file.type)) {
    elements.fileError.textContent = "Upload alleen een JPG of PNG bestand.";
    return false;
  }

  if (file.size > MAX_FILE_SIZE) {
    elements.fileError.textContent = "Bestand is te groot. Maximaal 5 MB.";
    return false;
  }

  const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
  elements.fileMeta.textContent = file.name + " (" + sizeMb + " MB)";
  return true;
}

function validateEmail() {
  elements.emailError.textContent = "";
  const email = elements.email.value.trim();
  const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  if (!isValid) {
    elements.emailError.textContent = "Vul een geldig e-mailadres in.";
  }
  return isValid;
}

function fileToBase64(file) {
  return new Promise((resolve, reject) => {
    if (!file) {
      resolve("");
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      const result = typeof reader.result === "string" ? reader.result : "";
      const base64 = result.includes(",") ? result.split(",")[1] : result;
      resolve(base64 || "");
    };
    reader.onerror = () => reject(new Error("Kon bestand niet lezen."));
    reader.readAsDataURL(file);
  });
}

function buildStyleIntro(styleLabel) {
  const styleName = (styleLabel || "jouw gekozen stijl").toLowerCase();
  return "Je voorkeur wijst naar " + styleName + ", een stijl die rust, balans en karakter samenbrengt. Met de juiste materialen, kleuren en indeling ontstaat een sfeer die persoonlijk en tijdloos aanvoelt.";
}

function renderHighlights(items) {
  elements.adviceBullets.innerHTML = "";
  (items || []).forEach((item) => {
    const li = document.createElement("li");
    li.className = "highlight-item";
    li.textContent = item;
    elements.adviceBullets.appendChild(li);
  });
}

function renderPalette(items) {
  elements.palette.innerHTML = "";
  (items || []).forEach((item) => {
    const swatch = document.createElement("article");
    swatch.className = "palette-item";

    const color = document.createElement("div");
    color.className = "swatch-color";
    color.style.background = item.hex;

    const meta = document.createElement("div");
    meta.className = "swatch-meta";

    const title = document.createElement("strong");
    title.textContent = item.name;

    const code = document.createElement("span");
    code.textContent = item.hex;

    meta.appendChild(title);
    meta.appendChild(code);
    swatch.appendChild(color);
    swatch.appendChild(meta);
    elements.palette.appendChild(swatch);
  });
}

function renderMaterials(items) {
  elements.materials.innerHTML = "";
  (items || []).forEach((item) => {
    const block = document.createElement("article");
    block.className = "material-block";

    const title = document.createElement("h4");
    title.textContent = item.category;
    block.appendChild(title);

    const recList = document.createElement("ul");
    (item.recommendations || []).forEach((value) => {
      const li = document.createElement("li");
      li.textContent = value;
      recList.appendChild(li);
    });
    block.appendChild(recList);

    const doLine = document.createElement("p");
    doLine.innerHTML = "<strong>Aanrader:</strong> " + (item.do || []).join("; ");
    block.appendChild(doLine);

    const dontLine = document.createElement("p");
    dontLine.innerHTML = "<strong>Liever vermijden:</strong> " + (item.dont || []).join("; ");
    block.appendChild(dontLine);

    elements.materials.appendChild(block);
  });
}

function renderLayoutTips(items) {
  elements.layoutTips.innerHTML = "";
  (items || []).forEach((item, index) => {
    const card = document.createElement("article");
    card.className = "tip-card";

    const indexLabel = document.createElement("span");
    indexLabel.className = "tip-index";
    indexLabel.textContent = String(index + 1);

    const text = document.createElement("p");
    text.textContent = item;

    card.appendChild(indexLabel);
    card.appendChild(text);
    elements.layoutTips.appendChild(card);
  });
}

function renderProductIdeas(items) {
  elements.productIdeas.innerHTML = "";
  (items || []).forEach((item) => {
    const card = document.createElement("article");
    card.className = "product-card";

    const title = document.createElement("h4");
    title.textContent = item.category;

    const specs = document.createElement("p");
    specs.innerHTML = "<strong>Voorstel:</strong> " + item.exampleSpecs;

    const material = document.createElement("p");
    material.innerHTML = "<strong>Materiaal:</strong> " + (item.material || "n.v.t.");

    const color = document.createElement("p");
    color.innerHTML = "<strong>Kleurhint:</strong> " + (item.colorHint || "n.v.t.");

    card.appendChild(title);
    card.appendChild(specs);
    card.appendChild(material);
    card.appendChild(color);
    elements.productIdeas.appendChild(card);
  });
}

function createImageBlock(title, imageDataUrl, helperText, emptyText) {
  const wrapper = document.createElement("figure");
  wrapper.className = "image-card";

  if (imageDataUrl) {
    const image = document.createElement("img");
    image.src = imageDataUrl;
    image.alt = title;
    wrapper.appendChild(image);
  } else {
    const placeholder = document.createElement("div");
    placeholder.className = "image-placeholder";
    placeholder.textContent = emptyText || "Het inspiratiebeeld is nog niet beschikbaar.";
    wrapper.appendChild(placeholder);
  }

  if (helperText) {
    const helper = document.createElement("p");
    helper.className = "hint";
    helper.textContent = helperText;
    wrapper.appendChild(helper);
  }

  return wrapper;
}

function renderImages(moodboardImageDataUrl, roomPreviewImageDataUrl) {
  elements.moodboardImage.innerHTML = "";
  elements.roomPreviewImage.innerHTML = "";

  elements.moodboardImage.appendChild(
    createImageBlock("Moodboard", moodboardImageDataUrl, "", "Het moodboard is nog niet beschikbaar.")
  );
  elements.roomPreviewImage.appendChild(
    createImageBlock(
      "Ruimte preview",
      roomPreviewImageDataUrl,
      "Inspiratiebeeld — kan afwijken van de werkelijkheid.",
      "Het inspiratiebeeld is nog niet beschikbaar."
    )
  );
}

function setReportIntroContent(json) {
  const paletteNames = (json.palette || []).slice(0, 3).map((item) => item.name).join(", ");
  elements.styleEditorialIntro.textContent = buildStyleIntro(json.styleLabel);
  elements.paletteIntro.textContent = paletteNames
    ? "Dit palet met " + paletteNames + " brengt rust, warmte en samenhang in je interieur."
    : "Dit kleurpalet ondersteunt een rustige en uitnodigende sfeer in je ruimte.";
  elements.materialsIntro.textContent = "Deze materiaalkeuzes sluiten aan op je stijlprofiel en zorgen voor een gebalanceerde mix van comfort, textuur en uitstraling.";
  elements.layoutIntro.textContent = "Gebruik deze praktische richtlijnen om meer rust, logica en ruimtelijkheid in je indeling te brengen.";
  elements.productIntro.textContent = "Deze suggesties helpen je om stijl, materiaal en kleur concreet te vertalen naar je interieur.";
}

function ease(type, t) {
  if (type === "easeOutQuad") return 1 - (1 - t) * (1 - t);
  if (type === "easeInOutSine") return -(Math.cos(Math.PI * t) - 1) / 2;
  if (type === "easeInOutCubic") return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
  if (type === "easeOutQuart") return 1 - Math.pow(1 - t, 4);
  return t;
}

function computeSimulatedProgress(elapsedMs) {
  const phase = PHASES.find((item) => elapsedMs >= item.startMs && elapsedMs <= item.endMs) || PHASES[PHASES.length - 1];

  if (elapsedMs <= EXPECTED_TOTAL_TIME) {
    const local = (elapsedMs - phase.startMs) / Math.max(1, phase.endMs - phase.startMs);
    const eased = ease(phase.easing, Math.max(0, Math.min(1, local)));
    const value = phase.start + (phase.end - phase.start) * eased;
    return Math.min(PHASE_PROGRESS_LIMITS[3], value);
  }

  const overtime = elapsedMs - EXPECTED_TOTAL_TIME;
  const creep = Math.log1p(overtime / 3000) * 1.2;
  return Math.min(98.6, PHASE_PROGRESS_LIMITS[3] + creep);
}

function computePhaseLabel(elapsedMs) {
  const phase = PHASES.find((item) => elapsedMs >= item.startMs && elapsedMs <= item.endMs) || PHASES[PHASES.length - 1];
  return phase.label;
}

function renderProgress() {
  elements.loadingProgressBar.style.width = state.progressValue + "%";
  elements.loadingPercent.textContent = Math.round(state.progressValue) + "%";
  const track = elements.loadingState.querySelector(".progress-track");
  if (track) {
    track.setAttribute("aria-valuenow", String(Math.round(state.progressValue)));
  }
}

function rotateStatusMessage() {
  elements.loadingMessage.textContent = ROTATING_STATUS_MESSAGES[state.statusIndex % ROTATING_STATUS_MESSAGES.length];
  state.statusIndex += 1;
}

function startLoading() {
  state.isGenerating = true;
  state.progressValue = 0;
  state.progressStartedAt = Date.now();
  state.statusIndex = 0;
  state.lateMessageShown = false;

  elements.loadingState.hidden = false;
  elements.loadingState.classList.add("is-visible");
  elements.loadingStep.textContent = PHASES[0].label;
  elements.loadingMessage.textContent = "Dit duurt meestal ongeveer 2 minuten.";
  if (elements.loadingHelperLine) {
    elements.loadingHelperLine.textContent = "Even geduld, we stellen jouw persoonlijke interieuradvies samen.";
  }
  if (elements.loadingLateMessage) {
    elements.loadingLateMessage.hidden = true;
  }

  renderProgress();

  if (state.progressTimer) window.clearInterval(state.progressTimer);
  if (state.statusTimer) window.clearInterval(state.statusTimer);

  state.progressTimer = window.setInterval(() => {
    const elapsed = Date.now() - state.progressStartedAt;
    const simulated = computeSimulatedProgress(elapsed);
    state.progressValue = Math.max(state.progressValue, simulated);
    elements.loadingStep.textContent = computePhaseLabel(elapsed);
    renderProgress();

    if (!state.lateMessageShown && elapsed >= LATE_STAGE_MESSAGE_DELAY && elements.loadingLateMessage) {
      state.lateMessageShown = true;
      elements.loadingLateMessage.hidden = false;
    }
  }, PROGRESS_UPDATE_INTERVAL);

  state.statusTimer = window.setInterval(() => {
    rotateStatusMessage();
  }, STATUS_MESSAGE_INTERVAL);
}

function finishProgressToHundred() {
  return new Promise((resolve) => {
    const tick = () => {
      if (state.progressValue >= 100) {
        resolve();
        return;
      }
      state.progressValue = Math.min(100, state.progressValue + 3.5);
      elements.loadingStep.textContent = PHASES[3].label;
      renderProgress();
      window.setTimeout(tick, 20);
    };
    tick();
  });
}

async function stopLoading(success) {
  if (state.progressTimer) {
    window.clearInterval(state.progressTimer);
    state.progressTimer = null;
  }
  if (state.statusTimer) {
    window.clearInterval(state.statusTimer);
    state.statusTimer = null;
  }

  if (success) {
    await finishProgressToHundred();
    await new Promise((resolve) => window.setTimeout(resolve, 120));
  }

  state.isGenerating = false;
  elements.loadingState.classList.remove("is-visible");
  elements.loadingState.hidden = true;
}

// Pollt GET /api/generations/{id} elke 3 seconden totdat status completed of failed is
async function pollGeneration(generationId, csrfToken) {
  const maxAttempts = 100; // 100 × 3s = 5 minuten
  for (let i = 0; i < maxAttempts; i++) {
    await new Promise(resolve => setTimeout(resolve, 3000));
    const res  = await fetch(`/api/generations/${generationId}`, {
      headers: { "Accept": "application/json", "X-CSRF-TOKEN": csrfToken }
    });
    const json = await res.json();
    if (json.status === "completed" || json.status === "failed") return json;
  }
  throw new Error("Genereren duurt te lang. Probeer het later opnieuw.");
}

async function handleGenerate() {
  if (state.isGenerating) return;

  clearErrors();

  if (!validateStep1() || !validateEmail()) return;

  const file = state.selectedFile;
  if (!validateFile(file)) return;

  elements.generateBtn.disabled = true;
  elements.sendStatus.textContent = "Resultaat wordt gegenereerd...";
  startLoading();

  let generated = false;

  try {
    const imageBase64 = await fileToBase64(file);
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute("content");

    // Stap 1: job inplannen — server reageert direct met 202
    const startRes = await fetch("/api/generate", {
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrf },
      body: JSON.stringify({
        style: elements.style.value,
        moodWords: elements.moodWords.value,
        colors: elements.colors.value,
        name: elements.name.value,
        email: elements.email.value,
        marketingOptIn: elements.marketingOptIn.checked,
        imageBase64
      })
    });

    const startJson = await startRes.json();
    if (!startRes.ok) throw new Error(startJson.error || "Starten mislukt.");

    // Stap 2: pollen totdat de job klaar is (elke 3 seconden, max 5 minuten)
    const json = await pollGeneration(startJson.generationId, csrf);
    if (json.status === "failed") throw new Error(json.error || "Genereren mislukt.");

    state.generatedResultId = json.resultId;
    elements.sendBtn.disabled = false;
    elements.sendStatus.textContent = "Resultaat klaar. Je kunt dit nu naar e-mail sturen.";

    elements.styleLabel.textContent = json.styleLabel;
    setReportIntroContent(json);
    renderHighlights(json.adviceBullets || []);
    renderPalette(json.palette || []);
    renderLayoutTips(json.layoutTips || []);
    renderMaterials(json.materials || []);
    renderProductIdeas(json.productIdeas || []);
    renderImages(json.moodboardImageDataUrl, json.roomPreviewImageDataUrl);

    // Toon e-mailstatus bovenaan de resultaten
    const emailNotice = document.getElementById('emailNotice');
    if (emailNotice) {
      if (json.emailError) {
        // Alleen tonen als de backend expliciet een fout meldt
        emailNotice.textContent = 'Je resultaat is gegenereerd, maar het versturen van de e-mail is niet gelukt.';
        emailNotice.className = 'email-notice email-notice--warning';
      } else if (elements.email.value) {
        // E-mailadres ingevuld: bericht wordt verstuurd (eventueel via queue)
        emailNotice.textContent = 'Je interieuradvies is gegenereerd en e-mail is verstuurd.';
        emailNotice.className = 'email-notice email-notice--success';
      }
      emailNotice.hidden = false;
    }

    generated = true;
  } catch (error) {
    elements.sendStatus.textContent = error.message;
  } finally {
    await stopLoading(generated);
    elements.generateBtn.disabled = false;

    if (generated) {
      // Verberg alle stap-kaarten zodat stap 3 niet meer zichtbaar is
      stepCards.forEach((card) => (card.hidden = true));

      elements.results.hidden = false;

      // updateStepper(5) markeert alle 4 stappen als afgerond (✓)
      updateStepper(5);

      const header = document.getElementById("mainHeader");
      header.querySelector("h1").textContent = "Jouw interieuradvies is klaar";
      header.querySelector("p").textContent  = "Bekijk jouw woonstijl, moodboard en ruimte-inspiratie op basis van jouw keuzes.";
      header.hidden = false;
      header.scrollIntoView({ behavior: "auto", block: "start" });
    }
  }
}

async function handleSendEmail() {
  clearErrors();

  if (!state.generatedResultId) {
    elements.sendStatus.textContent = "Genereer eerst een resultaat.";
    return;
  }

  if (!validateEmail()) return;

  elements.sendBtn.disabled = true;
  elements.sendStatus.textContent = "E-mail wordt verzonden...";

  try {
    const response = await fetch("/api/send", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
      },
      body: JSON.stringify({
        resultId: state.generatedResultId,
        name: elements.name.value,
        email: elements.email.value,
        marketingOptIn: elements.marketingOptIn.checked
      })
    });

    const json = await response.json();
    if (!response.ok) throw new Error(json.error || "Verzenden mislukt.");

    elements.sendStatus.textContent = "E-mail is verstuurd.";
  } catch (error) {
    elements.sendStatus.textContent = error.message;
  } finally {
    elements.sendBtn.disabled = false;
  }
}

function resetAll() {
  form.reset();
  state.generatedResultId = "";
  state.selectedFile = null;
  state.selectedMoodWords = new Set();
  state.selectedColors = new Set();
  elements.fileMeta.textContent = "Geen bestand geselecteerd.";
  elements.sendBtn.disabled = true;
  elements.results.hidden = true;
  elements.loadingState.hidden = true;
  elements.loadingState.classList.remove("is-visible");
  clearErrors();
  syncStyleCards();
  syncMoodChipsFromInput();
  syncColorSwatchesFromInput();

  // Header-tekst terugzetten naar de start-versie
  const header = document.getElementById("mainHeader");
  header.querySelector("h1").textContent = "Ontdek jouw woonstijl";
  header.querySelector("p").textContent  = "Beantwoord een paar korte vragen en ontvang een persoonlijk interieuradvies, moodboard en ruimte-inspiratie.";

  showStep(1);
}

document.getElementById("nextStep1").addEventListener("click", () => {
  if (validateStep1()) showStep(2);
});

document.getElementById("backStep2").addEventListener("click", () => showStep(1));
document.getElementById("nextStep2").addEventListener("click", () => showStep(3));
document.getElementById("backStep3").addEventListener("click", () => showStep(2));

styleCards.forEach((card) => {
  card.addEventListener("click", () => {
    elements.style.value = card.dataset.styleCard;
    syncStyleCards();
    validateStep1();
  });
});

elements.style.addEventListener("change", syncStyleCards);

moodChips.forEach((chip) => {
  chip.addEventListener("click", () => {
    if (state.selectedMoodWords.has(chip.dataset.mood)) {
      state.selectedMoodWords.delete(chip.dataset.mood);
    } else {
      state.selectedMoodWords.add(chip.dataset.mood);
    }
    updateInputFromSet(elements.moodWords, state.selectedMoodWords);
    syncMoodChipsFromInput();
  });
});

elements.moodWords.addEventListener("input", syncMoodChipsFromInput);

colorSwatches.forEach((swatch) => {
  swatch.addEventListener("click", () => {
    if (state.selectedColors.has(swatch.dataset.colorName)) {
      state.selectedColors.delete(swatch.dataset.colorName);
    } else {
      state.selectedColors.add(swatch.dataset.colorName);
    }
    updateInputFromSet(elements.colors, state.selectedColors);
    syncColorSwatchesFromInput();
  });
});

elements.colors.addEventListener("input", syncColorSwatchesFromInput);

elements.roomPhoto.addEventListener("change", (event) => {
  const file = event.target.files && event.target.files[0];
  state.selectedFile = file || null;
  validateFile(state.selectedFile);
});

elements.generateBtn.addEventListener("click", handleGenerate);
elements.sendBtn.addEventListener("click", handleSendEmail);
document.getElementById("restartBtn").addEventListener("click", resetAll);

ensureLoadingTextNodes();
showStep(1);
syncStyleCards();
syncMoodChipsFromInput();
syncColorSwatchesFromInput();
elements.loadingState.hidden = true;
