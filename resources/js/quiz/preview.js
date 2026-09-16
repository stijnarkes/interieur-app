import { renderQuestionStep } from "./components/questionStep.js";
import { renderSectionTransition } from "./components/sectionTransition.js";
import { renderStyleResult } from "./components/styleResult.js";
import { renderBasePaletteStep } from "./components/basePaletteStep.js";
import { renderReportTeaser } from "./components/reportTeaser.js";
import { renderLeadForm } from "./components/lead.js";
import { renderAccentColorStep } from "./components/accentColorStep.js";

/**
 * Boot voor de admin-voorbeeldpagina's (zie QuizPreviewController) — rendert precies hetzelfde
 * scherm als de echte quiz, met dezelfde componenten/CSS, maar volledig los van
 * quiz.js/state.js: die laatste schrijft naar dezelfde localStorage-sleutel als een echte
 * bezoekerssessie op dit apparaat, en een voorbeeld mag die nooit overschrijven. Bewaart daarom
 * zelf, puur in het geheugen, alleen wat nodig is om een klik visueel te laten reageren.
 */
function initPreview(root) {
  const payload = JSON.parse(root.dataset.preview);

  if (payload.type === "question") {
    initQuestionPreview(root, payload.question);
  } else if (payload.type === "transition") {
    renderSectionTransition(root, {
      sectionIndex: payload.sectionIndex,
      totalSections: payload.totalSections,
      section: payload.section,
      onContinue: () => {},
      onBack: () => {},
    });
  } else if (payload.type === "result") {
    initResultPreview(root, payload.result);
  }
}

function initQuestionPreview(root, question) {
  let selectedIds = [];

  function rerender() {
    renderQuestionStep(root, question, selectedIds, (optionId) => {
      const maxSelections = question.maxSelections ?? 1;
      if (selectedIds.includes(optionId)) {
        selectedIds = selectedIds.filter((id) => id !== optionId);
      } else if (maxSelections <= 1) {
        selectedIds = [optionId];
      } else if (selectedIds.length < maxSelections) {
        selectedIds = [...selectedIds, optionId];
      } else {
        return;
      }
      rerender();
    });
  }

  rerender();
}

function initResultPreview(root, result) {
  const styleResultMount = root.querySelector("#styleResultMount");
  const basePaletteMount = root.querySelector("#basePaletteMount");
  const accentColorMount = root.querySelector("#accentColorMount");
  const reportTeaserMount = root.querySelector("#reportTeaserMount");
  const leadMount = root.querySelector("#quizLeadMount");

  renderStyleResult(styleResultMount, result);

  const showReportAndLead = () => {
    renderReportTeaser(reportTeaserMount, { result });
    renderLeadForm(leadMount, { result, previewMode: true });
  };

  const renderAccentStep = (basePaletteColors) => {
    if (result.accentColorOptions?.length > 0) {
      renderAccentColorStep(accentColorMount, {
        options: result.accentColorOptions,
        resultUuid: result.resultUuid,
        basePaletteColors,
        previewMode: true,
        onDone: showReportAndLead,
      });
    } else {
      showReportAndLead();
    }
  };

  if (result.basePaletteOptions?.length > 0) {
    renderBasePaletteStep(basePaletteMount, {
      options: result.basePaletteOptions,
      resultUuid: result.resultUuid,
      primaryStyleLabel: result.primaryStyle?.label ?? "",
      previewMode: true,
      onDone: (chosenPalette) => renderAccentStep(chosenPalette.colors ?? []),
    });
  } else {
    renderAccentStep([]);
  }
}

export { initPreview };
