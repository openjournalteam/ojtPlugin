const colors = require("tailwindcss/colors");
const colorVariable = require("@mertasan/tailwindcss-variables/colorVariable");

const themes = {
  primary: {
    50: colorVariable("var(--colors-primary-50)"),
    100: colorVariable("var(--colors-primary-100)"),
    200: colorVariable("var(--colors-primary-200)"),
    300: colorVariable("var(--colors-primary-300)"),
    400: colorVariable("var(--colors-primary-400)"),
    500: colorVariable("var(--colors-primary-500)"),
    600: colorVariable("var(--colors-primary-600)"),
    700: colorVariable("var(--colors-primary-700)"),
    800: colorVariable("var(--colors-primary-800)"),
    900: colorVariable("var(--colors-primary-900)"),
  },
  danger: {
    50: colorVariable("var(--colors-danger-50)"),
    100: colorVariable("var(--colors-danger-100)"),
    200: colorVariable("var(--colors-danger-200)"),
    300: colorVariable("var(--colors-danger-300)"),
    400: colorVariable("var(--colors-danger-400)"),
    500: colorVariable("var(--colors-danger-500)"),
    600: colorVariable("var(--colors-danger-600)"),
    700: colorVariable("var(--colors-danger-700)"),
    800: colorVariable("var(--colors-danger-800)"),
    900: colorVariable("var(--colors-danger-900)"),
  },
  success: {
    50: colorVariable("var(--colors-success-50)"),
    100: colorVariable("var(--colors-success-100)"),
    200: colorVariable("var(--colors-success-200)"),
    300: colorVariable("var(--colors-success-300)"),
    400: colorVariable("var(--colors-success-400)"),
    500: colorVariable("var(--colors-success-500)"),
    600: colorVariable("var(--colors-success-600)"),
    700: colorVariable("var(--colors-success-700)"),
    800: colorVariable("var(--colors-success-800)"),
    900: colorVariable("var(--colors-success-900)"),
  },
  warning: {
    50: colorVariable("var(--colors-warning-50)"),
    100: colorVariable("var(--colors-warning-100)"),
    200: colorVariable("var(--colors-warning-200)"),
    300: colorVariable("var(--colors-warning-300)"),
    400: colorVariable("var(--colors-warning-400)"),
    500: colorVariable("var(--colors-warning-500)"),
    600: colorVariable("var(--colors-warning-600)"),
    700: colorVariable("var(--colors-warning-700)"),
    800: colorVariable("var(--colors-warning-800)"),
    900: colorVariable("var(--colors-warning-900)"),
  },
};

module.exports = {
  content: ["./**/*.tpl", "./assets/**/*.js"],
  prefix: "ojt-",
  darkMode: "class",
  // important: "#ojt-plugin",
  theme: {
    extend: {
      colors: {
        primary: themes.primary,
        danger: themes.danger,
        success: themes.success,
        warning: themes.warning,
      },
    },
    variables: {
      DEFAULT: {
        colors: {
          primary: colors.purple,
          danger: colors.rose,
          success: colors.green,
          warning: colors.yellow,
        },
      },
      '[data-theme="fuchsia"]': {
        colors: {
          primary: colors.fuchsia,
        },
      },
      '[data-theme="amber"]': {
        colors: {
          primary: colors.amber,
        },
      },
      '[data-theme="lime"]': {
        colors: {
          primary: colors.lime,
        },
      },
      '[data-theme="green"]': {
        colors: {
          primary: colors.green,
        },
      },
      '[data-theme="emerald"]': {
        colors: {
          primary: colors.emerald,
        },
      },
      '[data-theme="teal"]': {
        colors: {
          primary: colors.teal,
        },
      },
      '[data-theme="cyan"]': {
        colors: {
          primary: colors.cyan,
        },
      },
      '[data-theme="pink"]': {
        colors: {
          primary: colors.pink,
        },
      },
      '[data-theme="indigo"]': {
        colors: {
          primary: colors.indigo,
        },
      },
      '[data-theme="violet"]': {
        colors: {
          primary: colors.violet,
        },
      },
      '[data-theme="sky"]': {
        colors: {
          primary: colors.sky,
        },
      },
    },
  },
  plugins: [
    require("@tailwindcss/forms"),
    require("@mertasan/tailwindcss-variables")({
      colorVariables: true,
    }),
  ],
};
