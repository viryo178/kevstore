"use client";

import { motion } from "framer-motion";

interface AnimatedTitleFMProps {
  open?: boolean;
}

export function AnimatedTitleFM({ open = true }: AnimatedTitleFMProps) {
  return (
    <motion.h1
      className="px-6 text-center text-4xl font-semibold leading-tight tracking-normal text-white sm:text-5xl"
      initial={{ opacity: 0, y: 18 }}
      animate={open ? { opacity: 1, y: 0 } : { opacity: 0, y: 18 }}
      transition={{ duration: 0.7, ease: [0.16, 1, 0.3, 1] }}
    >
      Welcome to the
      <span className="mt-2 block bg-gradient-to-r from-[#6f4aff] via-[#5b35f2] to-[#4922E5] bg-clip-text text-transparent">
        AI-Powered World
      </span>
    </motion.h1>
  );
}
