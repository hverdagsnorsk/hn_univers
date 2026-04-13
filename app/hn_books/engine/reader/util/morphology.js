export function generateNounForms(lemma, gender, pluralType="regular") {

  const forms = {
    sg_indef: lemma,
    sg_def: "",
    pl_indef: "",
    pl_def: ""
  };

  if (gender === "n") {

    forms.sg_def = lemma + "et";

    if (pluralType === "zero") {
      forms.pl_indef = lemma;
      forms.pl_def = lemma + "ene";
    }
    else {
      forms.pl_indef = lemma;
      forms.pl_def = lemma + "ene";
    }

  }

  if (gender === "m") {

    forms.sg_def = lemma + "en";
    forms.pl_indef = lemma + "er";
    forms.pl_def = lemma + "ene";

  }

  if (gender === "f") {

    forms.sg_def = lemma + "a";
    forms.pl_indef = lemma + "er";
    forms.pl_def = lemma + "ene";

  }

  return forms;
}