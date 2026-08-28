import { QUESTIONS } from "./data.js";
import { STYLE_PROFILES, getStyleProfile } from "./styleProfiles.js";

function findQuestion(questionId) {
  return QUESTIONS.find((question) => question.id === questionId);
}

function findOption(questionId, optionId) {
  const question = findQuestion(questionId);
  return question?.options.find((option) => option.id === optionId) ?? null;
}

function getSelectedOptions(answers) {
  return Object.entries(answers)
    .map(([questionId, optionId]) => findOption(questionId, optionId))
    .filter(Boolean);
}

/**
 * Eenvoudige score: elke keuze geeft 1 punt aan haar primaryStyle. Generiek — kent geen
 * enkele vraag/optie expliciet, en bevat bewust geen gewogen sub-scores.
 */
function calculateScores(answers) {
  const styleTotals = Object.fromEntries(STYLE_PROFILES.map((style) => [style.key, 0]));

  for (const option of getSelectedOptions(answers)) {
    if (option.primaryStyle && option.primaryStyle in styleTotals) {
      styleTotals[option.primaryStyle] += 1;
    }
  }

  return styleTotals;
}

// Percentage wordt alleen intern gebruikt om te sorteren/rangschikken — de resultatenpagina
// toont bewust geen exacte percentages (de test is daar niet wetenschappelijk genoeg voor).
function rankStyles(styleTotals) {
  const sum = Object.values(styleTotals).reduce((a, b) => a + b, 0);
  return STYLE_PROFILES
    .map((style) => ({
      id: style.key,
      label: style.label,
      points: styleTotals[style.key],
      percentage: sum > 0 ? Math.round((styleTotals[style.key] / sum) * 100) : 0,
    }))
    .sort((a, b) => b.points - a.points);
}

/**
 * Bepaalt de primaire ("beste match") en secundaire ("past ook goed bij jou") stijl uit de
 * opgetelde punten, en levert de bijbehorende, vaste stijlinhoud uit styleProfiles.js.
 */
function composeResult(answers) {
  const styleTotals = calculateScores(answers);
  const ranked = rankStyles(styleTotals);
  const topStyles = ranked.filter((style) => style.points > 0).slice(0, 3);

  const winner = topStyles[0];
  const runnerUp = topStyles[1];
  const primaryStyle = winner ? getStyleProfile(winner.id) : null;
  const secondaryStyle = runnerUp ? getStyleProfile(runnerUp.id) : null;

  return {
    // Rijke profielen die de resultatenpagina gebruikt.
    primaryStyle,
    secondaryStyle,
    topStyles,
    styleTotals,

    // Vlakke velden voor terugwaartse compatibiliteit met lead-capture, PDF en e-mail.
    resultName: primaryStyle ? primaryStyle.label : "Jouw persoonlijke woonstijl",
    description: primaryStyle ? primaryStyle.longDescription : "",
    traits: primaryStyle ? primaryStyle.traits : [],
  };
}

export { calculateScores, composeResult, findOption, findQuestion, getSelectedOptions };
