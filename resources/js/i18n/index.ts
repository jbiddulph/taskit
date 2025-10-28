import { createI18n } from 'vue-i18n';
import en from '../locales/en.json';
import es from '../locales/es.json';
import fr from '../locales/fr.json';
import de from '../locales/de.json';
import pt from '../locales/pt.json';
import it from '../locales/it.json';
import nl from '../locales/nl.json';
import ru from '../locales/ru.json';
import ja from '../locales/ja.json';
import ko from '../locales/ko.json';
import zh from '../locales/zh.json';
import ar from '../locales/ar.json';
import he from '../locales/he.json';

const messages = {
  en,
  es,
  fr,
  de,
  pt,
  it,
  nl,
  ru,
  ja,
  ko,
  zh,
  ar,
  he,
};

// Get locale from localStorage or default to 'en'
const savedLocale = localStorage.getItem('locale') || 'en';

export const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  messages,
  globalInjection: true,
});

// Export a function to change locale
export const setLocale = (locale: string) => {
  i18n.global.locale.value = locale;
  localStorage.setItem('locale', locale);
  
  // Update HTML lang attribute
  document.documentElement.lang = locale;
  
  // Update HTML dir attribute for RTL languages
  const rtlLanguages = ['ar', 'he'];
  document.documentElement.dir = rtlLanguages.includes(locale) ? 'rtl' : 'ltr';
};

// Initialize HTML attributes
document.documentElement.lang = savedLocale;
const rtlLanguages = ['ar', 'he'];
document.documentElement.dir = rtlLanguages.includes(savedLocale) ? 'rtl' : 'ltr';
