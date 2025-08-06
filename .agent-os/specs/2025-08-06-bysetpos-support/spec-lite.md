# Spec Summary (Lite)

Implement RFC 5545 BYSETPOS parameter for advanced occurrence selection from expanded RRULE sets. Enables complex patterns like "last Sunday of March" by combining with existing BY* rules to first expand occurrences, then select specific positions using positive/negative indexing.