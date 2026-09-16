// Muteerbaar, gedeeld object (zelfde in-place-mutatietechniek als de *_COPY-objecten in copy.js)
// zodat elke consument (bv. partnerInvite.js) de actuele vlag leest zonder een eigen fetch te
// hoeven doen — zie remoteConfig.js's loadRemoteQuizConfig(), dat dit ná het ophalen vult.
const FEATURE_FLAGS = {
  partnerFeatureEnabled: false,
};

export { FEATURE_FLAGS };
