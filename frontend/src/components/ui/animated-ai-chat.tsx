"use client";

import * as React from "react";
import { useCallback, useEffect, useRef, useState } from "react";
import {
  AlertTriangle,
  CheckCircle2,
  Clipboard,
  Command,
  Edit3,
  Eye,
  FileText,
  FolderKanban,
  LoaderIcon,
  Mail,
  Paperclip,
  PenTool,
  SendIcon,
  Shield,
  ShoppingBag,
  Sparkles,
  Trash2,
  Users,
  XCircle,
  XIcon,
} from "lucide-react";
import { AnimatePresence, motion } from "framer-motion";
import { cn } from "@/lib/utils";
import type { ActiveView, ChatMessage, CommandRun, Conversation, Project, PromptTemplate } from "@/App";

interface UseAutoResizeTextareaProps {
  minHeight: number;
  maxHeight?: number;
}

function useAutoResizeTextarea({ minHeight, maxHeight }: UseAutoResizeTextareaProps) {
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  const adjustHeight = useCallback(
    (reset?: boolean) => {
      const textarea = textareaRef.current;
      if (!textarea) return;

      if (reset) {
        textarea.style.height = `${minHeight}px`;
        return;
      }

      textarea.style.height = `${minHeight}px`;
      const newHeight = Math.max(
        minHeight,
        Math.min(textarea.scrollHeight, maxHeight ?? Number.POSITIVE_INFINITY),
      );
      textarea.style.height = `${newHeight}px`;
    },
    [minHeight, maxHeight],
  );

  useEffect(() => {
    const textarea = textareaRef.current;
    if (textarea) textarea.style.height = `${minHeight}px`;
  }, [minHeight]);

  useEffect(() => {
    const handleResize = () => adjustHeight();
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, [adjustHeight]);

  return { textareaRef, adjustHeight };
}

interface CommandSuggestion {
  icon: React.ReactNode;
  label: string;
  prefix: string;
}

interface TextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  containerClassName?: string;
  showRing?: boolean;
}

function TypewriterText({ text }: { text: string }) {
  const [visibleText, setVisibleText] = useState("");

  useEffect(() => {
    let index = 0;
    let deleting = false;
    let timeoutId = 0;

    const tick = () => {
      if (!deleting) {
        index += 1;
        setVisibleText(text.slice(0, index));

        if (index >= text.length) {
          deleting = true;
          timeoutId = window.setTimeout(tick, 1400);
          return;
        }

        timeoutId = window.setTimeout(tick, 42);
        return;
      }

      index -= 1;
      setVisibleText(text.slice(0, Math.max(index, 0)));

      if (index <= 0) {
        deleting = false;
        timeoutId = window.setTimeout(tick, 450);
        return;
      }

      timeoutId = window.setTimeout(tick, 22);
    };

    timeoutId = window.setTimeout(tick, 250);
    return () => window.clearTimeout(timeoutId);
  }, [text]);

  return (
    <span className="inline-flex min-h-6 items-center">
      <span>{visibleText}</span>
      <motion.span
        className="ml-0.5 h-4 w-px bg-white/45"
        animate={{ opacity: [0, 1, 1, 0] }}
        transition={{ duration: 0.9, repeat: Infinity, ease: "linear" }}
      />
    </span>
  );
}

const Textarea = React.forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ className, containerClassName, showRing = true, ...props }, ref) => {
    const [isFocused, setIsFocused] = React.useState(false);

    return (
      <div className={cn("relative", containerClassName)}>
        <textarea
          className={cn(
            "flex min-h-20 w-full rounded-md border border-white/10 bg-transparent px-3 py-2 text-sm",
            "transition-all duration-200 ease-in-out placeholder:text-white/20",
            "disabled:cursor-not-allowed disabled:opacity-50",
            showRing ? "focus-visible:outline-none focus-visible:ring-0 focus-visible:ring-offset-0" : "",
            className,
          )}
          ref={ref}
          onFocus={(event) => {
            setIsFocused(true);
            props.onFocus?.(event);
          }}
          onBlur={(event) => {
            setIsFocused(false);
            props.onBlur?.(event);
          }}
          {...props}
        />

        {showRing && isFocused && (
          <motion.span
            className="pointer-events-none absolute inset-0 rounded-md ring-2 ring-violet-500/30 ring-offset-0"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
          />
        )}
      </div>
    );
  },
);
Textarea.displayName = "Textarea";

interface AnimatedAIChatProps {
  activeView: ActiveView;
  conversation: Conversation | null;
  messages: ChatMessage[];
  prompts: PromptTemplate[];
  projects: Project[];
  history: CommandRun[];
  isSending: boolean;
  isLoadingMessages: boolean;
  sidebarCollapsed: boolean;
  onSendMessage: (content: string) => Promise<void>;
  onUsePrompt: (prompt: PromptTemplate) => void;
}

export function AnimatedAIChat({
  activeView,
  conversation,
  messages,
  prompts,
  projects,
  history,
  isSending,
  isLoadingMessages,
  sidebarCollapsed,
  onSendMessage,
  onUsePrompt,
}: AnimatedAIChatProps) {
  const [value, setValue] = useState("");
  const [attachments, setAttachments] = useState<string[]>([]);
  const [activeSuggestion, setActiveSuggestion] = useState<number>(-1);
  const [showCommandPalette, setShowCommandPalette] = useState(false);
  const [mousePosition, setMousePosition] = useState({ x: 0, y: 0 });
  const [inputFocused, setInputFocused] = useState(false);
  const { textareaRef, adjustHeight } = useAutoResizeTextarea({ minHeight: 44, maxHeight: 120 });
  const commandPaletteRef = useRef<HTMLDivElement>(null);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const isTyping = inputFocused && value.trim().length > 0 && !isSending;
  const pointerStyle = {
    "--pointer-x": `${mousePosition.x}px`,
    "--pointer-y": `${mousePosition.y}px`,
  } as React.CSSProperties;

  const commandSuggestions: CommandSuggestion[] = [
    { icon: <ShoppingBag className="h-4 w-4" />, label: "Cek Stok", prefix: "berapa stok hari ini" },
    { icon: <PenTool className="h-4 w-4" />, label: "Tambah Akun", prefix: "tambah" },
    { icon: <FileText className="h-4 w-4" />, label: "Format Bulk", prefix: "tambah\nusername|password|catatan" },
    { icon: <Sparkles className="h-4 w-4" />, label: "Bantuan", prefix: "bantuan" },
  ];

  useEffect(() => {
    if (value.startsWith("/") && !value.includes(" ")) {
      setShowCommandPalette(true);
      const matchingSuggestionIndex = commandSuggestions.findIndex((cmd) => cmd.prefix.startsWith(value));
      setActiveSuggestion(matchingSuggestionIndex >= 0 ? matchingSuggestionIndex : -1);
    } else {
      setShowCommandPalette(false);
    }
  }, [value]);

  useEffect(() => {
    const scrollToLatest = () => messagesEndRef.current?.scrollIntoView({ behavior: "smooth", block: "end" });
    scrollToLatest();
    const firstFrame = window.requestAnimationFrame(scrollToLatest);
    const timeout = window.setTimeout(scrollToLatest, 80);
    return () => {
      window.cancelAnimationFrame(firstFrame);
      window.clearTimeout(timeout);
    };
  }, [messages, isSending]);

  useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => setMousePosition({ x: e.clientX, y: e.clientY });
    window.addEventListener("mousemove", handleMouseMove);
    return () => window.removeEventListener("mousemove", handleMouseMove);
  }, []);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Node;
      const commandButton = document.querySelector("[data-command-button]");
      if (commandPaletteRef.current && !commandPaletteRef.current.contains(target) && !commandButton?.contains(target)) {
        setShowCommandPalette(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const selectCommandSuggestion = (index: number) => {
    const selectedCommand = commandSuggestions[index];
    setValue(`${selectedCommand.prefix} `);
    setShowCommandPalette(false);
    requestAnimationFrame(() => adjustHeight());
  };

  const handleSendMessage = async () => {
    const content = value.trim();
    if (!content || isSending) return;

    setValue("");
    setAttachments([]);
    adjustHeight(true);
    await onSendMessage(content);
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (showCommandPalette) {
      if (e.key === "ArrowDown") {
        e.preventDefault();
        setActiveSuggestion((prev) => (prev < commandSuggestions.length - 1 ? prev + 1 : 0));
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        setActiveSuggestion((prev) => (prev > 0 ? prev - 1 : commandSuggestions.length - 1));
      } else if (e.key === "Tab" || e.key === "Enter") {
        e.preventDefault();
        if (activeSuggestion >= 0) selectCommandSuggestion(activeSuggestion);
      } else if (e.key === "Escape") {
        e.preventDefault();
        setShowCommandPalette(false);
      }
      return;
    }

    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      void handleSendMessage();
    }
  };

  const composerBox = (
    <motion.div className="pointer-events-auto relative mx-auto w-full max-w-5xl rounded-2xl border border-white/[0.08] bg-[#1f1f22]/90 text-left shadow-2xl backdrop-blur-2xl" initial={{ scale: 0.98 }} animate={{ scale: 1 }}>
      <AnimatePresence>
        {showCommandPalette && (
          <motion.div ref={commandPaletteRef} className="absolute bottom-full left-4 right-4 z-50 mb-2 overflow-hidden rounded-lg border border-white/10 bg-black/90 shadow-lg backdrop-blur-xl" initial={{ opacity: 0, y: 5 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: 5 }}>
            <div className="bg-black/95 py-1">
              {commandSuggestions.map((suggestion, index) => (
                <motion.button type="button" key={suggestion.prefix} className={cn("flex w-full cursor-pointer items-center gap-2 px-3 py-2.5 text-sm transition-colors", activeSuggestion === index ? "bg-white/10 text-white" : "text-white/70 hover:bg-white/5")} onClick={() => selectCommandSuggestion(index)}>
                  <span className="flex h-5 w-5 items-center justify-center text-white/60">{suggestion.icon}</span>
                  <span className="font-medium">{suggestion.label}</span>
                  <span className="ml-1 text-sm text-white/40">{suggestion.prefix}</span>
                </motion.button>
              ))}
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      <div className="p-4">
        <Textarea
          ref={textareaRef}
          value={value}
          onChange={(e) => {
            setValue(e.target.value);
            adjustHeight();
          }}
          onKeyDown={handleKeyDown}
          onFocus={() => setInputFocused(true)}
          onBlur={() => setInputFocused(false)}
          placeholder="Ask Violence AI a question..."
          containerClassName="w-full"
          className="chat-input-scroll min-h-14 w-full resize-none border-none bg-transparent px-4 py-3 text-xl text-white/90 placeholder:text-white/35 focus:outline-none"
          style={{ overflowY: "auto" }}
          showRing={false}
        />
      </div>

      <AnimatePresence>
        {attachments.length > 0 && (
          <motion.div className="flex flex-wrap gap-2 px-4 pb-3" initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }} exit={{ opacity: 0, height: 0 }}>
            {attachments.map((file, index) => (
              <motion.div key={file} className="flex items-center gap-2 rounded-lg bg-white/[0.03] px-3 py-1.5 text-xs text-white/70">
                <span>{file}</span>
                <button type="button" onClick={() => setAttachments((prev) => prev.filter((_, i) => i !== index))} className="text-white/40 transition-colors hover:text-white" aria-label="Remove attachment">
                  <XIcon className="h-3 w-3" />
                </button>
              </motion.div>
            ))}
          </motion.div>
        )}
      </AnimatePresence>

      <div className="flex items-center justify-between gap-4 border-t border-white/[0.05] p-4">
        <div className="flex items-center gap-3">
          <motion.button type="button" onClick={() => setAttachments((prev) => [...prev, `file-${Math.floor(Math.random() * 1000)}.pdf`])} whileTap={{ scale: 0.94 }} className="group relative rounded-lg p-2 text-white/40 transition-colors hover:text-white/90" aria-label="Attach file">
            <Paperclip className="h-4 w-4" />
            <span className="absolute inset-0 rounded-lg bg-white/[0.05] opacity-0 transition-opacity group-hover:opacity-100" />
          </motion.button>
          <motion.button type="button" data-command-button onClick={(e) => { e.stopPropagation(); setShowCommandPalette((prev) => !prev); }} whileTap={{ scale: 0.94 }} className={cn("group relative rounded-lg p-2 text-white/40 transition-colors hover:text-white/90", showCommandPalette && "bg-white/10 text-white/90")} aria-label="Open command palette">
            <Command className="h-4 w-4" />
            <span className="absolute inset-0 rounded-lg bg-white/[0.05] opacity-0 transition-opacity group-hover:opacity-100" />
          </motion.button>
        </div>

        <motion.button type="button" onClick={() => void handleSendMessage()} disabled={isSending || !value.trim()} className={cn("theme-hover-fill flex items-center gap-2 rounded-lg px-5 py-3 text-xl font-medium transition-all", value.trim() ? "bg-white text-[#0A0A0B] shadow-lg shadow-white/10" : "bg-white/[0.05] text-white/40")}>
          {isSending ? <LoaderIcon className="h-4 w-4 animate-spin" /> : <SendIcon className="h-4 w-4" />}
          <span>Send</span>
        </motion.button>
      </div>
    </motion.div>
  );

  if (activeView !== "chat") {
    if (activeView === "dashboard") {
      return <DashboardView historyCount={history.length} />;
    }
    if (activeView === "accounts") {
      return <AccountsManagementView />;
    }

    return (
      <Shell>
        {activeView === "prompts" && <PromptsView prompts={prompts} onUsePrompt={onUsePrompt} />}
        {activeView === "projects" && <ProjectsView projects={projects} />}
        {activeView === "history" && <HistoryView history={history} />}
        {activeView === "notifications" && <SimplePanel title="Notifikasi" subtitle="Pusat notifikasi aplikasi." items={["Belum ada notifikasi baru", "Aktivitas chat tersimpan otomatis", "Error AI dicatat ke log"]} />}
        {activeView === "activities" && <HistoryView history={history} />}
        {activeView === "employees" && <SimplePanel title="Kepegawaian" subtitle="Menu pengelolaan pegawai." items={["Data pegawai siap ditambahkan", "Akses dapat dihubungkan ke role admin", "Menu ini sudah siap menjadi modul dashboard"]} />}
        {activeView === "password" && <SimplePanel title="Ganti Password Exp" subtitle="Pengaturan password dan masa berlaku." items={["Form password bisa disambungkan ke tabel users", "Validasi minimal 6 karakter", "Session tetap memakai login CI3"]} />}
        {activeView === "upgrade" && <SimplePanel title="Upgrade plan" subtitle="Paket Plus aktif untuk workspace ini." items={["Akses prioritas", "Riwayat chat tersimpan", "Command workflow siap pakai"]} />}
        {activeView === "personalization" && <SimplePanel title="Personalization" subtitle="Preferensi tampilan dan gaya jawaban." items={["Theme: Dark", "Tone: Helpful", "Default model: local-demo-assistant"]} />}
        {activeView === "profile" && <SimplePanel title="Profile" subtitle="Data profil pengguna aktif." items={["Name: Demo User", "Email: violence@ai.local", "Role: admin"]} />}
        {activeView === "settings" && <SimplePanel title="Settings" subtitle="Pengaturan aplikasi Violence AI." items={["Sidebar state tersimpan", "Session memakai CI3", "Database: ai_chat"]} />}
        {activeView === "help" && <SimplePanel title="Help" subtitle="Panduan singkat." items={["Klik New Chat untuk mulai draft kosong", "Kirim pesan untuk menyimpan chat", "Gunakan /page, /clone, /figma, /improve untuk command"]} />}
      </Shell>
    );
  }

  return (
    <div className="relative flex min-h-screen w-full flex-col overflow-hidden bg-transparent text-white">
      <div className="interactive-chat-bg pointer-events-none fixed inset-0 z-0" style={pointerStyle}>
        <motion.div
          className="interactive-chat-bg__grid"
          animate={{ backgroundPosition: ["0px 0px", "44px 44px"] }}
          transition={{ duration: 18, repeat: Infinity, ease: "linear" }}
        />
        <motion.div
          className="interactive-chat-bg__sweep"
          animate={{ x: ["-35%", "135%"] }}
          transition={{ duration: 9, repeat: Infinity, ease: "easeInOut", repeatDelay: 1.5 }}
        />
        <div className="interactive-chat-bg__pointer" />
      </div>

      <motion.div className="relative z-10 flex min-h-screen flex-col" initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}>
        <header className="flex h-16 shrink-0 items-center justify-center border-b border-white/[0.04] px-5">
          <div className="max-w-3xl truncate text-sm font-medium text-white/60">
            {conversation?.title && conversation.title !== "New Chat" ? conversation.title : "New chat"}
          </div>
        </header>

        <div className="min-h-0 flex-1 overflow-y-auto px-5 pb-56 pt-7 sm:px-6 sm:pb-64 sm:pt-8">
          <div className="mx-auto w-full max-w-6xl sm:p-4">
            {messages.length === 0 && !isLoadingMessages ? (
              <div className="grid min-h-[calc(100vh-15rem)] place-items-center text-center">
                <div>
                  <h1 className="bg-gradient-to-r from-white/90 to-white/40 bg-clip-text pb-1 text-4xl font-medium tracking-normal text-transparent">
                    How can I help today?
                  </h1>
                  <p className="mt-3 text-base text-white/40">
                    <TypewriterText text="Tanya stok akun atau tambah akun Kevstore lewat chat." />
                  </p>
                  <div className="mt-6 flex flex-wrap items-center justify-center gap-2">
                    {commandSuggestions.map((suggestion, index) => (
                      <motion.button type="button" key={suggestion.prefix} onClick={() => selectCommandSuggestion(index)} className="group relative flex items-center gap-2 rounded-lg bg-white/[0.02] px-4 py-2.5 text-base text-white/60 transition-all hover:bg-white/[0.05] hover:text-white/90" initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: index * 0.1 }}>
                        {suggestion.icon}
                        <span>{suggestion.label}</span>
                        <span className="absolute inset-0 rounded-lg border border-white/[0.05]" />
                      </motion.button>
                    ))}
                  </div>
                  <div className="mt-10 w-[min(64rem,calc(100vw-2rem))]">
                    {composerBox}
                  </div>
                </div>
              </div>
            ) : (
              <div className="space-y-6">
                {messages.map((message) => (
                  <MessageBubble key={message.id} message={message} />
                ))}
                <AnimatePresence>
                  {isTyping && (
                    <motion.div
                      className="flex justify-end"
                      initial={{ opacity: 0, y: 10, scale: 0.98 }}
                      animate={{ opacity: 1, y: 0, scale: 1 }}
                      exit={{ opacity: 0, y: 8, scale: 0.98 }}
                      transition={{ duration: 0.18, ease: "easeOut" }}
                    >
                      <div className="theme-user-bubble rounded-2xl border px-5 py-4 text-xl text-white shadow-xl backdrop-blur-xl">
                        <div className="mb-1 text-base font-semibold uppercase tracking-[0.12em] text-white/45">You</div>
                        <TypingDots />
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
                {isSending && (
                  <div className="flex justify-start">
                    <div className="rounded-2xl border border-white/10 bg-white/[0.04] px-5 py-4 text-xl text-white/65 backdrop-blur-xl">
                      <span className="inline-flex items-center gap-2">
                        <span>Violence AI</span>
                        <TypingDots />
                      </span>
                    </div>
                  </div>
                )}
                <div ref={messagesEndRef} className="scroll-mb-64" />
              </div>
            )}
          </div>
        </div>

        {messages.length > 0 && (
        <div className={cn("pointer-events-none fixed bottom-0 left-0 right-0 z-20 bg-gradient-to-t from-[#050507] via-[#050507]/98 via-70% to-transparent px-5 pb-5 pt-16 sm:px-4 sm:pt-24", sidebarCollapsed ? "md:left-0" : "md:left-72")}>
          <motion.div className="pointer-events-auto relative mx-auto w-full max-w-5xl rounded-2xl border border-white/[0.08] bg-[#1f1f22]/90 shadow-2xl backdrop-blur-2xl" initial={{ scale: 0.98 }} animate={{ scale: 1 }}>
            <AnimatePresence>
              {showCommandPalette && (
                <motion.div ref={commandPaletteRef} className="absolute bottom-full left-4 right-4 z-50 mb-2 overflow-hidden rounded-lg border border-white/10 bg-black/90 shadow-lg backdrop-blur-xl" initial={{ opacity: 0, y: 5 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: 5 }}>
                  <div className="bg-black/95 py-1">
                    {commandSuggestions.map((suggestion, index) => (
                      <motion.button type="button" key={suggestion.prefix} className={cn("flex w-full cursor-pointer items-center gap-2 px-3 py-2.5 text-sm transition-colors", activeSuggestion === index ? "bg-white/10 text-white" : "text-white/70 hover:bg-white/5")} onClick={() => selectCommandSuggestion(index)}>
                        <span className="flex h-5 w-5 items-center justify-center text-white/60">{suggestion.icon}</span>
                        <span className="font-medium">{suggestion.label}</span>
                        <span className="ml-1 text-sm text-white/40">{suggestion.prefix}</span>
                      </motion.button>
                    ))}
                  </div>
                </motion.div>
              )}
            </AnimatePresence>

            <div className="p-4">
              <Textarea
                ref={textareaRef}
                value={value}
                onChange={(e) => {
                  setValue(e.target.value);
                  adjustHeight();
                }}
                onKeyDown={handleKeyDown}
                onFocus={() => setInputFocused(true)}
                onBlur={() => setInputFocused(false)}
                placeholder="Ask Violence AI a question..."
                containerClassName="w-full"
                className="chat-input-scroll min-h-14 w-full resize-none border-none bg-transparent px-4 py-3 text-xl text-white/90 placeholder:text-white/35 focus:outline-none"
                style={{ overflowY: "auto" }}
                showRing={false}
              />
            </div>

            <AnimatePresence>
              {attachments.length > 0 && (
                <motion.div className="flex flex-wrap gap-2 px-4 pb-3" initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: "auto" }} exit={{ opacity: 0, height: 0 }}>
                  {attachments.map((file, index) => (
                    <motion.div key={file} className="flex items-center gap-2 rounded-lg bg-white/[0.03] px-3 py-1.5 text-xs text-white/70">
                      <span>{file}</span>
                      <button type="button" onClick={() => setAttachments((prev) => prev.filter((_, i) => i !== index))} className="text-white/40 transition-colors hover:text-white" aria-label="Remove attachment">
                        <XIcon className="h-3 w-3" />
                      </button>
                    </motion.div>
                  ))}
                </motion.div>
              )}
            </AnimatePresence>

            <div className="flex items-center justify-between gap-4 border-t border-white/[0.05] p-4">
              <div className="flex items-center gap-3">
                <motion.button type="button" onClick={() => setAttachments((prev) => [...prev, `file-${Math.floor(Math.random() * 1000)}.pdf`])} whileTap={{ scale: 0.94 }} className="group relative rounded-lg p-2 text-white/40 transition-colors hover:text-white/90" aria-label="Attach file">
                  <Paperclip className="h-4 w-4" />
                  <span className="absolute inset-0 rounded-lg bg-white/[0.05] opacity-0 transition-opacity group-hover:opacity-100" />
                </motion.button>
                <motion.button type="button" data-command-button onClick={(e) => { e.stopPropagation(); setShowCommandPalette((prev) => !prev); }} whileTap={{ scale: 0.94 }} className={cn("group relative rounded-lg p-2 text-white/40 transition-colors hover:text-white/90", showCommandPalette && "bg-white/10 text-white/90")} aria-label="Open command palette">
                  <Command className="h-4 w-4" />
                  <span className="absolute inset-0 rounded-lg bg-white/[0.05] opacity-0 transition-opacity group-hover:opacity-100" />
                </motion.button>
              </div>

              <motion.button type="button" onClick={() => void handleSendMessage()} disabled={isSending || !value.trim()} className={cn("theme-hover-fill flex items-center gap-2 rounded-lg px-5 py-3 text-xl font-medium transition-all", value.trim() ? "bg-white text-[#0A0A0B] shadow-lg shadow-white/10" : "bg-white/[0.05] text-white/40")}>
                {isSending ? <LoaderIcon className="h-4 w-4 animate-spin" /> : <SendIcon className="h-4 w-4" />}
                <span>Send</span>
              </motion.button>
            </div>
          </motion.div>
        </div>
        )}
      </motion.div>

      {inputFocused && (
        <motion.div className="theme-cursor-glow pointer-events-none fixed z-0 h-[32rem] w-[32rem] rounded-full opacity-[0.018] blur-[84px]" animate={{ x: mousePosition.x - 256, y: mousePosition.y - 256 }} transition={{ type: "spring", damping: 25, stiffness: 150, mass: 0.5 }} />
      )}
    </div>
  );
}

function MessageBubble({ message }: { message: ChatMessage }) {
  const isUser = message.role === "user";
  const [copied, setCopied] = useState(false);
  const [expanded, setExpanded] = useState(false);
  const [openAccountIndex, setOpenAccountIndex] = useState<number | null>(null);
  const isLongMessage = message.content.length > 420 || message.content.split(/\r\n|\r|\n/).length > 8;
  const visibleContent = !expanded && isLongMessage ? `${message.content.slice(0, 420).trimEnd()}...` : message.content;
  const messageMetadata = React.useMemo(() => readMessageMetadata(message.metadata_json), [message.metadata_json]);
  const accountDetails = messageMetadata.accountDetails;
  const copyText = messageMetadata.copyText || message.content;

  const copyMessage = async () => {
    try {
      await navigator.clipboard.writeText(copyText);
      setCopied(true);
      window.setTimeout(() => setCopied(false), 1200);
    } catch {
      setCopied(false);
    }
  };

  return (
    <div className={cn("flex", isUser ? "justify-end" : "justify-start")}>
      <motion.div
        initial={{ opacity: 0, y: 10 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: isUser ? 0.18 : 0.55, ease: "easeOut" }}
        className={cn(
          "max-w-[88%] rounded-2xl border px-5 py-4 text-xl leading-9 shadow-xl backdrop-blur-xl sm:max-w-[82%]",
          isUser
            ? "theme-user-bubble text-white"
            : "border-white/12 bg-[#18181b]/95 text-white shadow-black/35"
        )}
      >
        <div className="mb-2 flex items-center justify-between gap-3 text-base font-semibold uppercase tracking-[0.12em] text-white/45">
          <span>{isUser ? "You" : "Violence AI"}</span>
          {!isUser && (
            <button
              type="button"
              onClick={() => void copyMessage()}
              className="group/copy inline-flex h-8 items-center gap-1.5 rounded-md px-2 text-sm normal-case tracking-normal text-white/45 transition hover:bg-white/10 hover:text-white"
              aria-label="Salin pesan AI"
              title="Salin pesan"
            >
              {copied ? <CheckCircle2 className="h-3.5 w-3.5" /> : <Clipboard className="h-3.5 w-3.5" />}
              <span className={cn(copied ? "inline" : "hidden group-hover/copy:inline group-focus-visible/copy:inline")}>
                {copied ? "Tersalin" : "Salin"}
              </span>
            </button>
          )}
        </div>
        <div className="whitespace-pre-wrap">{visibleContent}</div>
        {isLongMessage && (
          <button
            type="button"
            onClick={() => setExpanded((current) => !current)}
            className="mt-3 inline-flex rounded-md px-2 py-1 text-base font-semibold text-white/65 transition hover:bg-white/10 hover:text-white"
          >
            {expanded ? "Lihat lebih sedikit" : "Lihat lebih banyak"}
          </button>
        )}
        {!isUser && accountDetails.length > 0 && (
          <div className="mt-4 space-y-2 border-t border-white/10 pt-3">
            <div className="text-base font-semibold text-white/50">Detail akun</div>
            <div className="flex flex-wrap gap-2">
              {accountDetails.map((account, index) => (
                <button
                  key={`${account.id_akun ?? account.username ?? index}-${index}`}
                  type="button"
                  onClick={() => setOpenAccountIndex((current) => (current === index ? null : index))}
                  className={cn(
                    "rounded-lg px-3 py-1.5 text-base font-semibold transition",
                    openAccountIndex === index ? "bg-[var(--theme-400)] text-white" : "bg-white/[0.06] text-white/65 hover:bg-white/10 hover:text-white",
                  )}
                >
                  {index + 1}. {account.username || "Detail"}
                </button>
              ))}
            </div>

            {openAccountIndex !== null && accountDetails[openAccountIndex] && (
              <div className="rounded-xl border border-white/10 bg-black/25 p-3 text-base leading-7 text-white/75">
                <DetailLine label="Nama" value={accountDetails[openAccountIndex].nama_akun} />
                <DetailLine label="Username" value={accountDetails[openAccountIndex].username} />
                <DetailLine label="Password" value={accountDetails[openAccountIndex].password} />
                <DetailLine label="Website" value={accountDetails[openAccountIndex].website} />
                <DetailLine label="Kategori" value={accountDetails[openAccountIndex].kategori} />
                <DetailLine label="Status" value={accountDetails[openAccountIndex].status} />
                <DetailLine label="Max user" value={accountDetails[openAccountIndex].max_user} />
                <DetailLine label="Expired" value={accountDetails[openAccountIndex].expired_password} />
                <DetailLine label="Note" value={accountDetails[openAccountIndex].note} />
              </div>
            )}
          </div>
        )}
      </motion.div>
    </div>
  );
}

interface AccountDetail {
  id_akun?: number | string | null;
  nama_akun?: string | null;
  kategori?: string | null;
  status?: string | null;
  username?: string | null;
  password?: string | null;
  website?: string | null;
  max_user?: number | string | null;
  expired_password?: string | null;
  note?: string | null;
}

function readMessageMetadata(metadataJson?: string | null): { accountDetails: AccountDetail[]; copyText: string | null } {
  if (!metadataJson) return { accountDetails: [], copyText: null };
  try {
    const metadata = JSON.parse(metadataJson) as { account_details?: AccountDetail[]; copy_text?: string | null };
    return {
      accountDetails: Array.isArray(metadata.account_details) ? metadata.account_details : [],
      copyText: typeof metadata.copy_text === "string" && metadata.copy_text.trim() ? metadata.copy_text : null,
    };
  } catch {
    return { accountDetails: [], copyText: null };
  }
}

function DetailLine({ label, value }: { label: string; value?: string | number | null }) {
  return (
    <div className="grid grid-cols-[5.5rem_1fr] gap-3">
      <span className="text-white/40">{label}</span>
      <span className="break-words text-white/80">{value === null || value === undefined || value === "" ? "-" : value}</span>
    </div>
  );
}

function TypingDots() {
  return (
    <span className="inline-flex items-center gap-1">
      {[0, 1, 2].map((dot) => (
        <motion.span
          key={dot}
          className="h-1.5 w-1.5 rounded-full bg-white/80"
          animate={{ y: [0, -5, 0], opacity: [0.35, 1, 0.35] }}
          transition={{ duration: 0.75, repeat: Infinity, delay: dot * 0.14, ease: "easeInOut" }}
        />
      ))}
    </span>
  );
}

function DashboardView({ historyCount }: { historyCount: number }) {
  const [dashboardStats, setDashboardStats] = useState<Partial<AccountStats> & { total_akun?: number } | null>(null);
  const [availableAccounts, setAvailableAccounts] = useState<StoreAccount[]>([]);
  const [availableLimit, setAvailableLimit] = useState(10);
  const [availablePage, setAvailablePage] = useState(1);
  const [availableSearch, setAvailableSearch] = useState("");
  const [isLoadingAvailable, setIsLoadingAvailable] = useState(true);

  useEffect(() => {
    let active = true;

    const loadDashboard = async () => {
      setIsLoadingAvailable(true);
      try {
        const data = await localApiGet<{
          stats?: Partial<AccountStats> & { total_akun?: number };
          available_accounts?: StoreAccount[];
        }>("api/dashboard");

        if (!active) return;
        setDashboardStats(data.stats ?? null);
        setAvailableAccounts(data.available_accounts ?? []);
      } catch {
        if (!active) return;
        setDashboardStats(null);
        setAvailableAccounts([]);
      } finally {
        if (active) setIsLoadingAvailable(false);
      }
    };

    void loadDashboard();
    return () => {
      active = false;
    };
  }, []);

  const stats = [
    { label: "Verif", total: String(dashboardStats?.verif ?? 0), percent: "0%", icon: Shield, tone: "indigo" },
    { label: "Aktif", total: String(dashboardStats?.aktif ?? 0), percent: "18%", icon: CheckCircle2, tone: "green" },
    { label: "Deactivated", total: String(dashboardStats?.deactived ?? 0), percent: "0%", icon: XCircle, tone: "orange" },
    { label: "Belum Terjual", total: String(dashboardStats?.belum_terjual ?? 0), percent: "2%", icon: ShoppingBag, tone: "green" },
    { label: "Expired", total: "0", percent: "0%", icon: AlertTriangle, tone: "orange" },
  ] as const;

  const filteredAvailableAccounts = availableAccounts.filter((account) => {
    const haystack = [
      account.nama_akun,
      account.username,
      account.password,
      account.kategori,
      account.status,
      account.note,
    ].join(" ").toLowerCase();
    return haystack.includes(availableSearch.toLowerCase());
  });
  const availableTotalPages = Math.max(1, Math.ceil(filteredAvailableAccounts.length / availableLimit));
  const normalizedAvailablePage = Math.min(availablePage, availableTotalPages);
  const availableStartIndex = (normalizedAvailablePage - 1) * availableLimit;
  const visibleAvailableAccounts = filteredAvailableAccounts.slice(availableStartIndex, availableStartIndex + availableLimit);

  useEffect(() => {
    setAvailablePage(1);
  }, [availableLimit, availableSearch]);

  return (
    <div className="min-h-screen bg-transparent text-white">
      <main className="px-5 py-8 md:px-8">
        <div className="mb-5">
          <h1 className="text-2xl font-bold tracking-normal">Dashboard</h1>
          <p className="mt-1 text-sm font-semibold text-blue-200/80">Home <span className="mx-2 text-white/45">/</span> Dashboard</p>
        </div>

        <section className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.48fr)]">
          <div className="space-y-6">
            <div className="grid gap-6 md:grid-cols-3">
              {stats.slice(0, 3).map((item) => <StatCard key={item.label} {...item} />)}
            </div>
            <div className="grid gap-6 md:grid-cols-2">
              <StatCard {...stats[3]} wide />
              <StatCard {...stats[4]} wide />
            </div>
          </div>
          <div className="rounded-2xl border border-white/10 bg-black/30 p-5 shadow-xl shadow-black/20 backdrop-blur-2xl xl:min-h-[10rem]">
            <h2 className="text-lg font-semibold">Notifikasi Terbaru</h2>
            <p className="mt-7 text-sm text-blue-100/75">Tidak ada notifikasi</p>
          </div>
        </section>

        <section className="mt-7 rounded-2xl border border-white/10 bg-black/30 p-5 shadow-xl shadow-black/20 backdrop-blur-2xl">
          <div className="mb-8 flex items-center justify-between gap-4">
            <h2 className="text-lg font-semibold">Akun Tersedia <span className="text-sm font-normal text-blue-200">| Max User &lt; 5</span></h2>
            <span className="rounded-full border border-blue-300/15 px-3 py-1 text-xs text-blue-100/70">{historyCount} aktivitas</span>
          </div>

          <div className="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <label className="flex items-center gap-2 text-sm text-white">
              <select value={availableLimit} onChange={(event) => setAvailableLimit(Number(event.target.value))} className="h-9 rounded-lg border border-white/10 bg-black/35 px-3 outline-none">
                <option>10</option>
                <option>25</option>
                <option>50</option>
              </select>
              entries per page
            </label>
            <input value={availableSearch} onChange={(event) => setAvailableSearch(event.target.value)} className="h-9 rounded-lg border border-white/10 bg-black/35 px-3 text-sm outline-none placeholder:text-white/35" placeholder="Search..." />
          </div>

          <div className="overflow-x-auto">
            <table className="w-full min-w-[760px] border-collapse text-left text-sm">
              <thead>
                <tr className="border-b border-blue-300/10 text-blue-50">
                  {["Nama Akun", "Username", "Password", "Max User", "Kategori", "Aksi"].map((head) => (
                    <th key={head} className="px-2 py-3 font-bold">{head}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {isLoadingAvailable ? (
                  <tr><td className="px-2 py-8 text-blue-100/60" colSpan={6}>Memuat akun tersedia...</td></tr>
                ) : visibleAvailableAccounts.length === 0 ? (
                  <tr><td className="px-2 py-8 text-blue-100/60" colSpan={6}>Tidak ada akun tersedia.</td></tr>
                ) : visibleAvailableAccounts.map((account) => (
                  <tr key={account.id_akun} className="border-b border-blue-300/5 text-blue-50/95">
                    <td className="px-2 py-3 font-bold">{account.nama_akun}</td>
                    <td className="px-2 py-3">{account.username}</td>
                    <td className="px-2 py-3 font-mono text-xs text-cyan-300">{account.password}</td>
                    <td className="px-2 py-3">
                      <span className="rounded-lg border border-emerald-400 px-3 py-1 text-xs font-bold text-emerald-300">{formatMaxUser(account)}</span>
                    </td>
                    <td className="px-2 py-3">
                      <CategoryBadge value={account.kategori} />
                    </td>
                    <td className="px-2 py-3">
                      <div className="flex gap-2">
                        <button type="button" className="grid h-8 w-8 place-items-center rounded-md border border-white/10 bg-white/[0.04] text-white/70 transition hover:bg-white/10 hover:text-white"><Clipboard className="h-4 w-4" /></button>
                        <button type="button" className="grid h-8 w-8 place-items-center rounded-md border border-white/10 bg-white/[0.04] text-white/70 transition hover:bg-white/10 hover:text-white"><Edit3 className="h-4 w-4" /></button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <TablePagination
            page={normalizedAvailablePage}
            totalPages={availableTotalPages}
            totalEntries={filteredAvailableAccounts.length}
            pageSize={availableLimit}
            visibleCount={visibleAvailableAccounts.length}
            onPageChange={setAvailablePage}
            disabled={isLoadingAvailable}
          />
        </section>
      </main>
    </div>
  );
}

interface StoreAccount {
  id_akun: number | string;
  nama_akun: string;
  kategori: "private" | "sharing" | "belum_terjual";
  status: string;
  username: string;
  password: string;
  website?: string | null;
  note?: string | null;
  max_user: number | string;
  expired_password?: string | null;
}

interface AccountStats {
  total: number;
  verif: number;
  aktif: number;
  deactived: number;
  disable_x: number;
  disable_email: number;
  ban: number;
  belum_terjual: number;
}

const emptyAccountForm = {
  nama_akun: "",
  kategori: "private",
  status: "aktif",
  username: "",
  password: "",
  website: "",
  max_user: "0",
  expired_password: "",
  note: "",
};

function AccountsManagementView() {
  const [accounts, setAccounts] = useState<StoreAccount[]>([]);
  const [stats, setStats] = useState<AccountStats | null>(null);
  const [search, setSearch] = useState("");
  const [limit, setLimit] = useState(10);
  const [page, setPage] = useState(1);
  const [pagination, setPagination] = useState({ page: 1, limit: 10, total: 0, total_filtered: 0, total_pages: 1 });
  const [isLoading, setIsLoading] = useState(true);
  const [modal, setModal] = useState<"add" | "bulk" | "bulkEdit" | "edit" | null>(null);
  const [form, setForm] = useState(emptyAccountForm);
  const [bulkText, setBulkText] = useState("");
  const [bulkEditForm, setBulkEditForm] = useState({ kategori: "", status: "", max_user: "", expired_password: "", note: "" });
  const [selected, setSelected] = useState<StoreAccount | null>(null);
  const [selectedAccountIds, setSelectedAccountIds] = useState<Array<number | string>>([]);

  const loadAccounts = useCallback(async () => {
    setIsLoading(true);
    try {
      const data = await localApiGet<{
        accounts?: StoreAccount[];
        data?: StoreAccount[];
        stats: AccountStats;
        pagination?: typeof pagination;
        total?: number;
        total_filtered?: number;
      }>(
        `api/akun?limit=${limit}&offset=${(page - 1) * limit}&q=${encodeURIComponent(search)}`,
      );
      const rows = data.accounts ?? data.data ?? [];
      const [filteredRows, allRows] = await Promise.all([
        fetchAllAccounts(search),
        fetchAllAccounts(""),
      ]);
      setAccounts(rows);
      setSelectedAccountIds((current) => current.filter((id) => rows.some((account) => String(account.id_akun) === String(id))));
      setStats(data.stats ?? statsFromAccounts(allRows));
      const totalFiltered = data.total_filtered ?? data.pagination?.total_filtered ?? filteredRows.length;
      const total = data.total ?? data.pagination?.total ?? allRows.length ?? totalFiltered;
      setPagination({
        page,
        limit,
        total,
        total_filtered: totalFiltered,
        total_pages: Math.max(1, Math.ceil(totalFiltered / limit)),
      });
    } finally {
      setIsLoading(false);
    }
  }, [limit, page, search]);

  useEffect(() => {
    const timer = window.setTimeout(() => void loadAccounts(), 250);
    return () => window.clearTimeout(timer);
  }, [loadAccounts]);

  useEffect(() => {
    setPage(1);
  }, [limit, search]);

  const openAdd = () => {
    setForm(emptyAccountForm);
    setSelected(null);
    setModal("add");
  };

  const openEdit = (account: StoreAccount) => {
    setSelected(account);
    setForm({
      nama_akun: account.nama_akun || "",
      kategori: account.kategori || "private",
      status: account.status || "aktif",
      username: account.username || "",
      password: account.password || "",
      website: account.website || "",
      max_user: String(account.max_user ?? 0),
      expired_password: account.expired_password || "",
      note: account.note || "",
    });
    setModal("edit");
  };

  const saveAccount = async () => {
    const payload = { ...form, max_user: Number(form.max_user || 0), id_akun: selected?.id_akun };
    await localApiRequest(modal === "edit" ? `api/akun/${selected?.id_akun}` : "api/akun", modal === "edit" ? "PATCH" : "POST", payload);
    setModal(null);
    await loadAccounts();
  };

  const saveBulk = async () => {
    await localApiRequest("api/akun/bulk", "POST", { bulk_accounts: bulkText });
    setModal(null);
    await loadAccounts();
  };

  const deleteAccount = async (account: StoreAccount) => {
    await localApiRequest(`api/akun/${account.id_akun}`, "DELETE");
    await loadAccounts();
  };

  const selectedAccounts = accounts.filter((account) => selectedAccountIds.some((id) => String(id) === String(account.id_akun)));
  const allVisibleSelected = accounts.length > 0 && accounts.every((account) => selectedAccountIds.some((id) => String(id) === String(account.id_akun)));

  const toggleAccount = (account: StoreAccount) => {
    setSelectedAccountIds((current) => {
      const exists = current.some((id) => String(id) === String(account.id_akun));
      return exists ? current.filter((id) => String(id) !== String(account.id_akun)) : [...current, account.id_akun];
    });
  };

  const toggleAllVisible = () => {
    setSelectedAccountIds((current) => {
      if (allVisibleSelected) {
        return current.filter((id) => !accounts.some((account) => String(account.id_akun) === String(id)));
      }
      const next = [...current];
      accounts.forEach((account) => {
        if (!next.some((id) => String(id) === String(account.id_akun))) next.push(account.id_akun);
      });
      return next;
    });
  };

  const openBulkEdit = () => {
    if (selectedAccounts.length === 0) return;
    setBulkEditForm({ kategori: "", status: "", max_user: "", expired_password: "", note: "" });
    setModal("bulkEdit");
  };

  const saveBulkEdit = async () => {
    const updates = selectedAccounts.map((account) => {
      const update: Record<string, string | number> = { id_akun: account.id_akun };
      if (bulkEditForm.kategori) update.kategori = bulkEditForm.kategori;
      if (bulkEditForm.status) update.status = bulkEditForm.status;
      if (bulkEditForm.max_user !== "") update.max_user = Number(bulkEditForm.max_user || 0);
      if (bulkEditForm.expired_password) update.expired_password = bulkEditForm.expired_password;
      if (bulkEditForm.note) update.note = bulkEditForm.note;
      return update;
    });

    await localApiRequest("api/akun/bulk", "PATCH", { accounts: updates });
    setModal(null);
    setSelectedAccountIds([]);
    await loadAccounts();
  };

  const statCards = [
    { label: "Total Akun", sub: "Semua", value: stats?.total ?? 0, percent: "100%", icon: Users, tone: "indigo" as const },
    { label: "Verif", sub: "Total", value: stats?.verif ?? 0, percent: "0%", icon: Shield, tone: "indigo" as const },
    { label: "Aktif", sub: "Total", value: stats?.aktif ?? 0, percent: "18%", icon: CheckCircle2, tone: "green" as const },
    { label: "Deactived", sub: "Total", value: stats?.deactived ?? 0, percent: "0%", icon: XCircle, tone: "orange" as const },
    { label: "Disable X", sub: "Total", value: stats?.disable_x ?? 0, percent: "0%", icon: XIcon, tone: "orange" as const },
    { label: "Disable Email", sub: "Total", value: stats?.disable_email ?? 0, percent: "0%", icon: Mail, tone: "orange" as const },
    { label: "Ban", sub: "Total", value: stats?.ban ?? 0, percent: "0%", icon: SlashIcon, tone: "orange" as const },
    { label: "Belum Terjual", sub: "Total", value: stats?.belum_terjual ?? 0, percent: "2%", icon: ShoppingBag, tone: "green" as const },
  ];

  return (
    <div className="min-h-screen bg-transparent px-4 py-8 text-white md:px-7">
      <div className="mx-auto w-full max-w-[98rem]">
        <h1 className="mb-4 text-2xl font-bold tracking-normal">Kelola Akun</h1>
        <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
          {statCards.map((item) => (
            <StatCard key={item.label} label={item.label} total={String(item.value)} percent={item.percent} icon={item.icon} tone={item.tone} />
          ))}
        </div>
      </div>

      <section className="mx-auto mt-7 w-full max-w-[98rem] rounded-2xl border border-white/20 bg-black/30 p-5 shadow-2xl shadow-black/30 backdrop-blur-2xl">
        <div className="mb-7 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <h2 className="text-lg font-semibold">Data Seluruh Akun</h2>
          <div className="flex flex-wrap gap-2">
            <button type="button" onClick={() => setModal("bulk")} className="rounded-xl border border-white/15 bg-white/8 px-5 py-3 text-sm font-bold text-white/85 shadow-lg shadow-black/15 backdrop-blur-xl transition hover:border-violet-200/50 hover:bg-violet-400/35 hover:text-white hover:shadow-[0_0_28px_rgba(167,139,250,0.45)]">Bulk Tambah</button>
            <button type="button" onClick={openBulkEdit} disabled={selectedAccounts.length === 0} className="rounded-xl border border-white/15 bg-white/8 px-5 py-3 text-sm font-bold text-white/85 shadow-lg shadow-black/15 backdrop-blur-xl transition hover:border-violet-200/50 hover:bg-violet-400/35 hover:text-white hover:shadow-[0_0_28px_rgba(167,139,250,0.45)] disabled:cursor-not-allowed disabled:opacity-45">Bulk Edit ({selectedAccounts.length})</button>
            <button type="button" onClick={openAdd} className="rounded-xl border border-white/15 bg-white/8 px-5 py-3 text-sm font-bold text-white/85 shadow-lg shadow-black/15 backdrop-blur-xl transition hover:border-violet-200/50 hover:bg-violet-400/35 hover:text-white hover:shadow-[0_0_28px_rgba(167,139,250,0.45)]">+ Tambah Akun</button>
          </div>
        </div>

        <div className="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
          <label className="flex items-center gap-2 text-sm text-white">
            <select value={limit} onChange={(event) => setLimit(Number(event.target.value))} className="h-9 rounded-lg border border-white/10 bg-black/20 px-3 outline-none backdrop-blur-xl">
              <option>10</option>
              <option>25</option>
              <option>50</option>
            </select>
            entries per page
          </label>
          <input value={search} onChange={(event) => setSearch(event.target.value)} className="h-9 rounded-lg border border-white/10 bg-black/20 px-3 text-sm outline-none backdrop-blur-xl placeholder:text-white/35" placeholder="Search..." />
        </div>

        <div className="overflow-x-auto">
          <table className="w-full min-w-[900px] border-collapse text-left text-sm">
            <thead>
              <tr className="border-b border-white/10 text-white/90">
                <th className="px-2 py-3 font-bold">
                  <button type="button" onClick={toggleAllVisible} className={cn("grid h-4 w-4 place-items-center rounded border text-[10px] transition", allVisibleSelected ? "border-blue-300 bg-blue-300 text-black" : "border-blue-300/30 text-transparent hover:border-blue-300")}>
                    ✓
                  </button>
                </th>
                {["Nama", "Username", "Password", "Kategori", "Status", "Expired", "Aksi"].map((head) => (
                  <th key={head} className="px-2 py-3 font-bold">{head}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr><td className="px-2 py-8 text-white/50" colSpan={8}>Memuat akun...</td></tr>
              ) : accounts.map((account) => (
                <tr key={account.id_akun} className="border-b border-white/5 text-white/90">
                  <td className="px-2 py-3">
                    <button type="button" onClick={() => toggleAccount(account)} className={cn("grid h-4 w-4 place-items-center rounded border text-[10px] transition", selectedAccountIds.some((id) => String(id) === String(account.id_akun)) ? "border-blue-300 bg-blue-300 text-black" : "border-blue-300/30 text-transparent hover:border-blue-300")}>
                      ✓
                    </button>
                  </td>
                  <td className="px-2 py-3 font-bold">{account.nama_akun}</td>
                  <td className="px-2 py-3">{account.username}</td>
                  <td className="px-2 py-3 font-bold text-white/80">{account.password}</td>
                  <td className="px-2 py-3"><CategoryBadge value={account.kategori} /></td>
                  <td className="px-2 py-3"><StatusBadge value={account.status} /></td>
                  <td className="px-2 py-3">{account.expired_password || "-"}</td>
                  <td className="px-2 py-3">
                    <div className="flex gap-2">
                      <button type="button" className="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.03] text-blue-300"><Eye className="h-4 w-4" /></button>
                      <button type="button" onClick={() => openEdit(account)} className="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.03] text-yellow-300"><Edit3 className="h-4 w-4" /></button>
                      <button type="button" onClick={() => void deleteAccount(account)} className="grid h-8 w-8 place-items-center rounded-lg border border-white/10 bg-white/[0.03] text-red-300"><Trash2 className="h-4 w-4" /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <TablePagination
          page={pagination.page}
          totalPages={pagination.total_pages}
          totalEntries={pagination.total_filtered}
          pageSize={pagination.limit}
          visibleCount={accounts.length}
          onPageChange={setPage}
          disabled={isLoading}
        />
      </section>

      <AnimatePresence>
        {modal && (
          <AccountModal title={modal === "bulk" ? "Bulk Tambah Akun" : modal === "bulkEdit" ? `Bulk Edit (${selectedAccounts.length})` : modal === "edit" ? `Edit Akun #${selected?.id_akun}` : "Tambah Akun"} onClose={() => setModal(null)}>
            {modal === "bulk" ? (
              <BulkForm value={bulkText} onChange={setBulkText} onSave={saveBulk} onCancel={() => setModal(null)} />
            ) : modal === "bulkEdit" ? (
              <BulkEditForm selectedAccounts={selectedAccounts} form={bulkEditForm} onChange={setBulkEditForm} onSave={saveBulkEdit} onCancel={() => setModal(null)} />
            ) : (
              <AccountForm form={form} onChange={setForm} onSave={saveAccount} onCancel={() => setModal(null)} />
            )}
          </AccountModal>
        )}
      </AnimatePresence>
    </div>
  );
}

function AccountModal({ title, children, onClose }: { title: string; children: React.ReactNode; onClose: () => void }) {
  return (
    <motion.div className="fixed inset-0 z-50 grid place-items-center bg-black/65 p-4 backdrop-blur-sm" initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}>
      <motion.div className="max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-2xl border border-white/10 bg-black/75 p-5 shadow-2xl shadow-black/60 backdrop-blur-2xl" initial={{ y: 24, scale: 0.98 }} animate={{ y: 0, scale: 1 }} exit={{ y: 24, scale: 0.98 }}>
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-2xl font-bold">{title}</h2>
          <button type="button" onClick={onClose} className="rounded-lg p-2 text-white/55 hover:bg-white/10 hover:text-white"><XIcon className="h-5 w-5" /></button>
        </div>
        {children}
      </motion.div>
    </motion.div>
  );
}

function AccountForm({ form, onChange, onSave, onCancel }: { form: typeof emptyAccountForm; onChange: (form: typeof emptyAccountForm) => void; onSave: () => void; onCancel: () => void }) {
  const set = (key: keyof typeof emptyAccountForm, value: string) => onChange({ ...form, [key]: value });
  return (
    <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-5 backdrop-blur-2xl">
      <div className="grid gap-5 md:grid-cols-2">
        <Field label="Nama Akun" value={form.nama_akun} onChange={(value) => set("nama_akun", value)} />
        <SelectField label="Kategori" value={form.kategori} onChange={(value) => set("kategori", value)} options={[["private", "Private"], ["sharing", "Sharing"], ["belum_terjual", "Belum Terjual"]]} />
        <SelectField label="Status" value={form.status} onChange={(value) => set("status", value)} options={[["aktif", "Aktif"], ["verif", "Verif"], ["deactived", "Deactived"], ["umur", "Disable X"], ["terjual", "Terjual"]]} />
        <Field label="Username" value={form.username} onChange={(value) => set("username", value)} />
        <Field label="Password" value={form.password} onChange={(value) => set("password", value)} />
        <Field label="Website" value={form.website} onChange={(value) => set("website", value)} />
        <Field label="Max User" value={form.max_user} onChange={(value) => set("max_user", value)} type="number" />
        <Field label="Expired Password" value={form.expired_password} onChange={(value) => set("expired_password", value)} type="date" />
      </div>
      <label className="mt-5 block text-sm font-bold text-white/90">
        Note
        <textarea value={form.note} onChange={(event) => set("note", event.target.value)} className="mt-2 min-h-24 w-full rounded-xl border border-white/10 bg-black/25 px-3 py-3 outline-none focus:border-violet-200/30" />
      </label>
      <div className="mt-5 flex justify-end gap-2">
        <button type="button" onClick={onCancel} className="rounded-lg border border-white/10 bg-white/10 px-4 py-2 font-semibold text-white/75">Batal</button>
        <button type="button" onClick={() => void onSave()} className="rounded-lg border border-violet-200/25 bg-violet-100/15 px-5 py-2 font-semibold text-white hover:bg-violet-100/25">Simpan</button>
      </div>
    </div>
  );
}

function BulkForm({ value, onChange, onSave, onCancel }: { value: string; onChange: (value: string) => void; onSave: () => void; onCancel: () => void }) {
  return (
    <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-5 backdrop-blur-2xl">
      <h3 className="mb-5 text-lg font-bold">Tambah Stok Grok</h3>
      <p className="mb-7 text-sm text-violet-100/70">Satu akun per baris. Format: username|password|catatan</p>
      <label className="block text-sm font-bold">
        Daftar Akun
        <textarea value={value} onChange={(event) => onChange(event.target.value)} placeholder={"user1@gmail.com|password123|akun utama\nuser2@gmail.com|pass456\nuser3@gmail.com|mypass789|catatan opsional"} className="mt-2 min-h-60 w-full rounded-xl border border-white/10 bg-black/25 px-3 py-3 text-violet-50 outline-none placeholder:text-white/30 focus:border-violet-200/30" />
      </label>
      <div className="mt-4 rounded-xl border border-violet-200/15 bg-violet-100/10 px-3 py-3 text-sm text-violet-50/80">Default: Nama Akun Grok, Kategori Belum Terjual, Status Aktif, Max User 0, expired dan tanggal dikosongkan.</div>
      <div className="mt-6 flex justify-end gap-2">
        <button type="button" onClick={onCancel} className="rounded-lg border border-white/10 bg-white/10 px-4 py-2 font-semibold text-white/75">Batal</button>
        <button type="button" onClick={() => void onSave()} className="rounded-lg border border-violet-200/25 bg-violet-100/15 px-5 py-2 font-semibold text-white hover:bg-violet-100/25">Simpan Semua</button>
      </div>
    </div>
  );
}

function BulkEditForm({
  selectedAccounts,
  form,
  onChange,
  onSave,
  onCancel,
}: {
  selectedAccounts: StoreAccount[];
  form: { kategori: string; status: string; max_user: string; expired_password: string; note: string };
  onChange: React.Dispatch<React.SetStateAction<{ kategori: string; status: string; max_user: string; expired_password: string; note: string }>>;
  onSave: () => void;
  onCancel: () => void;
}) {
  const set = (key: keyof typeof form, value: string) => onChange((current) => ({ ...current, [key]: value }));

  return (
    <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-5 backdrop-blur-2xl">
      <h3 className="mb-3 text-lg font-bold">Edit {selectedAccounts.length} Akun Terpilih</h3>
      <div className="mb-5 max-h-32 overflow-y-auto rounded-xl border border-white/10 bg-black/20 p-3 text-sm text-white/70">
        {selectedAccounts.map((account) => (
          <div key={account.id_akun} className="flex justify-between gap-3 border-b border-white/5 py-1 last:border-0">
            <span className="font-semibold text-white/90">{account.nama_akun}</span>
            <span className="truncate">{account.username}</span>
          </div>
        ))}
      </div>
      <p className="mb-5 text-sm text-violet-100/70">Isi hanya field yang ingin diubah untuk semua akun terpilih.</p>
      <div className="grid gap-4 md:grid-cols-2">
        <SelectField label="Kategori" value={form.kategori} onChange={(value) => set("kategori", value)} options={[["", "Tidak diubah"], ["private", "Private"], ["sharing", "Sharing"], ["belum_terjual", "Belum Terjual"]]} />
        <SelectField label="Status" value={form.status} onChange={(value) => set("status", value)} options={[["", "Tidak diubah"], ["aktif", "Aktif"], ["verif", "Verif"], ["deactived", "Deactived"], ["umur", "Disable X"], ["terjual", "Terjual"]]} />
        <Field label="Max User" value={form.max_user} onChange={(value) => set("max_user", value)} type="number" />
        <Field label="Expired Password" value={form.expired_password} onChange={(value) => set("expired_password", value)} type="date" />
      </div>
      <label className="mt-5 block text-sm font-bold text-white/90">
        Note
        <textarea value={form.note} onChange={(event) => set("note", event.target.value)} className="mt-2 min-h-24 w-full rounded-xl border border-white/10 bg-black/25 px-3 py-3 outline-none focus:border-violet-200/30" />
      </label>
      <div className="mt-5 flex justify-end gap-2">
        <button type="button" onClick={onCancel} className="rounded-lg border border-white/10 bg-white/10 px-4 py-2 font-semibold text-white/75">Batal</button>
        <button type="button" onClick={() => void onSave()} className="rounded-lg border border-violet-200/25 bg-violet-100/15 px-5 py-2 font-semibold text-white hover:bg-violet-100/25">Simpan Bulk Edit</button>
      </div>
    </div>
  );
}

function Field({ label, value, onChange, type = "text" }: { label: string; value: string; onChange: (value: string) => void; type?: string }) {
  return (
    <label className="block text-sm font-bold text-white/90">
      {label}
      <input type={type} value={value} onChange={(event) => onChange(event.target.value)} className="mt-2 h-11 w-full rounded-xl border border-white/10 bg-black/25 px-3 outline-none focus:border-violet-200/30" />
    </label>
  );
}

function SelectField({ label, value, onChange, options }: { label: string; value: string; onChange: (value: string) => void; options: [string, string][] }) {
  return (
    <label className="block text-sm font-bold text-white/90">
      {label}
      <select value={value} onChange={(event) => onChange(event.target.value)} className="mt-2 h-11 w-full rounded-xl border border-white/10 bg-black/25 px-3 outline-none focus:border-violet-200/30">
        {options.map(([optionValue, labelText]) => <option key={optionValue} value={optionValue}>{labelText}</option>)}
      </select>
    </label>
  );
}

function CategoryBadge({ value }: { value: string }) {
  const label = value === "belum_terjual" ? "Belum Terjual" : value === "sharing" ? "Sharing" : "Private";
  return <span className="rounded-lg border border-yellow-300 px-3 py-1 text-xs font-bold text-yellow-300">{label}</span>;
}

function StatusBadge({ value }: { value: string }) {
  const label = value === "aktif" ? "Aktif" : value === "verif" ? "Verif" : value === "deactived" ? "Deactived" : value === "terjual" ? "Terjual" : "Disable X";
  return <span className="rounded-lg border border-emerald-400 px-3 py-1 text-xs font-bold text-emerald-300">{label}</span>;
}

function TablePagination({
  page,
  totalPages,
  totalEntries,
  pageSize,
  visibleCount,
  onPageChange,
  disabled,
}: {
  page: number;
  totalPages: number;
  totalEntries: number;
  pageSize: number;
  visibleCount: number;
  onPageChange: (page: number | ((current: number) => number)) => void;
  disabled?: boolean;
}) {
  const safeTotalPages = Math.max(1, totalPages);
  const safePage = Math.min(Math.max(1, page), safeTotalPages);
  const start = totalEntries === 0 ? 0 : (safePage - 1) * pageSize + 1;
  const end = totalEntries === 0 ? 0 : Math.min(start + visibleCount - 1, totalEntries);
  const pages = paginationPages(safePage, safeTotalPages);

  return (
    <div className="mt-4 flex flex-col gap-3 rounded-xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-white sm:flex-row sm:items-center sm:justify-between">
      <div className="font-medium text-white/85">
        Menampilkan {start} sampai {end} dari {totalEntries} entries
      </div>
      <div className="flex flex-wrap items-center gap-1.5">
        <button
          type="button"
          disabled={disabled || safePage <= 1}
          onClick={() => onPageChange((current) => Math.max(1, current - 1))}
          className="grid h-8 min-w-8 place-items-center rounded-md border border-white/10 bg-white/[0.04] px-2 text-white/80 transition hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-35"
          aria-label="Previous page"
        >
          ‹
        </button>
        {pages.map((item, index) =>
          item === "..." ? (
            <span key={`${item}-${index}`} className="grid h-8 min-w-8 place-items-center text-white/40">...</span>
          ) : (
            <button
              key={item}
              type="button"
              disabled={disabled}
              onClick={() => onPageChange(item)}
              className={cn(
                "grid h-8 min-w-8 place-items-center rounded-md border px-2 text-sm font-semibold transition",
                item === safePage
                  ? "border-white bg-white text-[#09090b]"
                  : "border-white/10 bg-white/[0.04] text-white/85 hover:bg-white/10 hover:text-white",
              )}
            >
              {item}
            </button>
          ),
        )}
        <button
          type="button"
          disabled={disabled || safePage >= safeTotalPages}
          onClick={() => onPageChange((current) => Math.min(safeTotalPages, current + 1))}
          className="grid h-8 min-w-8 place-items-center rounded-md border border-white/10 bg-white/[0.04] px-2 text-white/80 transition hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-35"
          aria-label="Next page"
        >
          ›
        </button>
      </div>
    </div>
  );
}

function paginationPages(page: number, totalPages: number): Array<number | "..."> {
  if (totalPages <= 7) return Array.from({ length: totalPages }, (_, index) => index + 1);

  const pages = new Set([1, totalPages, page, page - 1, page + 1]);
  const sorted = Array.from(pages)
    .filter((item) => item >= 1 && item <= totalPages)
    .sort((a, b) => a - b);

  return sorted.reduce<Array<number | "...">>((items, item) => {
    const previous = items[items.length - 1];
    if (typeof previous === "number" && item - previous > 1) items.push("...");
    items.push(item);
    return items;
  }, []);
}

function formatMaxUser(account: StoreAccount) {
  const maxUser = Number(account.max_user ?? 0);
  if (account.kategori === "private") return `${maxUser} / 1`;
  if (account.kategori === "sharing") return `${maxUser} / 4`;
  return `${maxUser} / 0`;
}

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? "/";

function localApiUrl(path: string) {
  const apiPath = path.replace(/^\/+/, "");
  if (API_BASE_URL === "/" || API_BASE_URL === "") return `/${apiPath}`;
  return new URL(apiPath, API_BASE_URL).toString();
}

async function localApiGet<T>(path: string): Promise<T> {
  const response = await fetch(localApiUrl(path), { credentials: "include", headers: { Accept: "application/json" } });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.message || "Request gagal.");
  return adaptKevstoreResponse<T>(data);
}

async function localApiRequest<T = { message?: string }>(path: string, method: string, body?: unknown): Promise<T> {
  const response = await fetch(localApiUrl(path), {
    method,
    credentials: "include",
    headers: { Accept: "application/json", "Content-Type": "application/json" },
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.message || "Request gagal.");
  return adaptKevstoreResponse<T>(data);
}

async function fetchAllAccounts(search: string): Promise<StoreAccount[]> {
  const rows: StoreAccount[] = [];
  const pageSize = 200;

  for (let offset = 0; offset <= 10000; offset += pageSize) {
    const query = `api/akun?limit=${pageSize}&offset=${offset}&q=${encodeURIComponent(search)}`;
    const data = await localApiGet<{ accounts?: StoreAccount[]; data?: StoreAccount[] }>(query);
    const pageRows = data.accounts ?? data.data ?? [];
    rows.push(...pageRows);
    if (pageRows.length < pageSize) break;
  }

  return rows;
}

function statsFromAccounts(accounts: StoreAccount[]): AccountStats {
  return accounts.reduce<AccountStats>(
    (stats, account) => {
      const status = String(account.status || "").toLowerCase().replace(/[\s-]/g, "_");
      stats.total += 1;
      if (status === "verif") stats.verif += 1;
      if (status === "aktif") stats.aktif += 1;
      if (status === "deactived") stats.deactived += 1;
      if (status === "disable_x" || status === "umur") stats.disable_x += 1;
      if (status === "disable_email") stats.disable_email += 1;
      if (status === "ban") stats.ban += 1;
      if (account.kategori === "belum_terjual") stats.belum_terjual += 1;
      return stats;
    },
    { total: 0, verif: 0, aktif: 0, deactived: 0, disable_x: 0, disable_email: 0, ban: 0, belum_terjual: 0 },
  );
}

function adaptKevstoreResponse<T>(data: any): T {
  if (data && typeof data === "object" && Array.isArray(data.data) && !Array.isArray(data.accounts)) {
    return { ...data, accounts: data.data } as T;
  }
  return data as T;
}

function SlashIcon({ className }: { className?: string }) {
  return (
    <span className={cn("grid place-items-center", className)}>
      <span className="block h-8 w-1 rotate-[28deg] rounded-full bg-current" />
    </span>
  );
}

function StatCard({
  label,
  total,
  percent,
  icon: Icon,
  tone,
  wide,
}: {
  label: string;
  total: string;
  percent: string;
  icon: React.ComponentType<{ className?: string }>;
  tone: "indigo" | "green" | "orange";
  wide?: boolean;
}) {
  const toneClass = {
    indigo: "border-indigo-400/20 text-indigo-300 shadow-indigo-600/30",
    green: "border-emerald-400/20 text-emerald-300 shadow-emerald-600/30",
    orange: "border-orange-400/20 text-orange-300 shadow-orange-600/30",
  }[tone];
  const percentClass = tone === "green" ? "text-emerald-400" : tone === "orange" && label === "Belum Terjual" ? "text-yellow-300" : "text-rose-400";

  return (
    <div className={cn("rounded-2xl border border-white/30 bg-white/[0.08] p-5 shadow-xl shadow-black/20 backdrop-blur-2xl", wide && "min-h-40")}>
      <h2 className="text-xl font-semibold">{label} <span className="text-base font-normal text-blue-200">| Total</span></h2>
      <div className="mt-5 flex items-center gap-4">
        <div className={cn("grid h-16 w-16 place-items-center rounded-full border bg-white/[0.03] shadow-[0_0_32px]", toneClass)}>
          <Icon className="h-8 w-8" />
        </div>
        <div>
          <div className="text-4xl font-bold">{total}</div>
          <div className={cn("mt-1 text-base font-bold", percentClass)}>{percent}</div>
        </div>
      </div>
    </div>
  );
}

function Shell({ children }: { children: React.ReactNode }) {
  return (
    <div className="relative grid min-h-screen place-items-center overflow-hidden bg-transparent p-6 text-white">
      <div className="relative z-10 w-full max-w-4xl rounded-2xl border border-white/10 bg-black/30 p-6 shadow-2xl shadow-black/30 backdrop-blur-2xl">
        {children}
      </div>
    </div>
  );
}

function PromptsView({ prompts, onUsePrompt }: { prompts: PromptTemplate[]; onUsePrompt: (prompt: PromptTemplate) => void }) {
  return (
    <div>
      <ViewTitle title="Prompts" subtitle="Command yang sudah tersimpan di database." />
      <div className="grid gap-3 sm:grid-cols-2">
        {prompts.map((prompt) => (
          <button key={prompt.id} type="button" onClick={() => onUsePrompt(prompt)} className="rounded-xl border border-white/10 bg-white/[0.03] p-4 text-left transition hover:bg-white/[0.07]">
            <div className="mb-2 flex items-center gap-2 text-white"><FileText className="h-4 w-4" /> {prompt.title}</div>
            <div className="mb-3 text-xs text-violet-200/70">{prompt.command}</div>
            <p className="text-sm leading-6 text-white/50">{prompt.description}</p>
          </button>
        ))}
      </div>
    </div>
  );
}

function ProjectsView({ projects }: { projects: Project[] }) {
  return (
    <div>
      <ViewTitle title="Projects" subtitle="Workspace dari tabel projects." />
      <div className="grid gap-3 sm:grid-cols-2">
        {projects.map((project) => (
          <div key={project.id} className="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <div className="mb-2 flex items-center gap-2 text-white"><FolderKanban className="h-4 w-4" /> {project.name}</div>
            <p className="text-sm leading-6 text-white/50">{project.description || "No description"}</p>
          </div>
        ))}
      </div>
    </div>
  );
}

function HistoryView({ history }: { history: CommandRun[] }) {
  return (
    <div>
      <ViewTitle title="History" subtitle="Riwayat command run." />
      {history.length === 0 ? (
        <div className="rounded-xl border border-white/10 bg-white/[0.03] p-5 text-sm text-white/45">Belum ada command yang dijalankan.</div>
      ) : (
        <div className="space-y-2">
          {history.map((item) => (
            <div key={item.id} className="rounded-xl border border-white/10 bg-white/[0.03] p-4 text-sm text-white/65">
              <div className="font-medium text-white">{item.command}</div>
              <div className="text-xs text-white/35">{item.status} - {item.created_at}</div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

function SimplePanel({ title, subtitle, items }: { title: string; subtitle: string; items: string[] }) {
  return (
    <div>
      <ViewTitle title={title} subtitle={subtitle} />
      <div className="space-y-3">
        {items.map((item) => (
          <div key={item} className="rounded-xl border border-white/10 bg-white/[0.03] p-4 text-sm text-white/70">
            {item}
          </div>
        ))}
      </div>
    </div>
  );
}

function ViewTitle({ title, subtitle }: { title: string; subtitle: string }) {
  return (
    <div className="mb-5">
      <h1 className="text-2xl font-semibold text-white">{title}</h1>
      <p className="mt-1 text-sm text-white/45">{subtitle}</p>
    </div>
  );
}
