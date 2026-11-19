using System;
using System.Collections.Generic;
using System.Reflection;
using HarmonyLib;
using Verse;

[StaticConstructorOnStartup]
internal static class FrenchBtdDescriptionFix
{
    private const string LoaderTypeName =
        "RWM_BTD_Remix_GravshipBlueprints.BTD_GravshipDesignLoader";

    private static readonly Dictionary<string, string> TranslationKeys =
        new Dictionary<string, string>
        {
            { "Danube_Class_Runabout", "FrenchBtdDescription_Danube_Class_Runabout" },
            { "Delta_Class_Shuttle", "FrenchBtdDescription_Delta_Class_Shuttle" },
            { "Type_11a_Shuttlecraft", "FrenchBtdDescription_Type_11a_Shuttlecraft" },
            { "Type_11_Shuttlecraft", "FrenchBtdDescription_Type_11_Shuttlecraft" },
            { "Type_8_Shuttlecraft", "FrenchBtdDescription_Type_8_Shuttlecraft" },
            { "DujHod_Chariot", "FrenchBtdDescription_DujHod_Chariot" },
            { "Dynamic_Class_Freighter", "FrenchBtdDescription_Dynamic_Class_Freighter" },
            { "KT_400_Freighter", "FrenchBtdDescription_KT_400_Freighter" }
        };

    private static readonly FieldInfo LabelField;
    private static readonly FieldInfo DescriptionField;

    static FrenchBtdDescriptionFix()
    {
        Type loaderType = AccessTools.TypeByName(LoaderTypeName);
        if (loaderType == null)
        {
            throw new InvalidOperationException("Could not find " + LoaderTypeName + ".");
        }

        MethodInfo loadDesign = AccessTools.Method(
            loaderType,
            "LoadDesignFromFile",
            new[] { typeof(string) });
        if (loadDesign == null)
        {
            throw new InvalidOperationException("Could not find BTD LoadDesignFromFile(string).");
        }

        LabelField = AccessTools.Field(loadDesign.ReturnType, "label");
        DescriptionField = AccessTools.Field(loadDesign.ReturnType, "description");
        if (LabelField == null || DescriptionField == null)
        {
            throw new InvalidOperationException("The BTD design label or description field was not found.");
        }

        MethodInfo postfix = typeof(FrenchBtdDescriptionFix).GetMethod(
            nameof(AfterLoad),
            BindingFlags.Static | BindingFlags.NonPublic);
        new Harmony("copilot.french.btddescriptionfix").Patch(
            loadDesign,
            postfix: new HarmonyMethod(postfix));
    }

    private static void AfterLoad(object __result)
    {
        LoadedLanguage language = LanguageDatabase.activeLanguage;
        if (__result == null || language == null || language.FriendlyNameEnglish != "French")
        {
            return;
        }

        string label = LabelField.GetValue(__result) as string;
        string key;
        if (label == null || !TranslationKeys.TryGetValue(label, out key))
        {
            return;
        }

        TaggedString translated;
        if (Translator.TryTranslate(key, out translated))
        {
            DescriptionField.SetValue(__result, translated.ToString());
        }
    }
}
