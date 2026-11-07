"""Protocol-neutral, dependency-free helpers for rsctf process checkers.

Network and protocol code belongs in run.py. Copy this file together with
run.py; Repository Bindings prepares the whole checker directory, so sibling
imports work inside the checker sandbox.
"""

from dataclasses import dataclass
from enum import IntEnum
from functools import wraps
from ipaddress import ip_address
import os
import secrets
from typing import Callable, TypedDict, TypeVar


__all__ = [
    "AdContext",
    "KothContext",
    "Mumble",
    "Offline",
    "ad_checker",
    "checker",
    "koth_checker",
    "run_ad_checker",
    "run_koth_checker",
]


class Verdict(IntEnum):
    OK = 0
    MUMBLE = 1
    OFFLINE = 2
    INTERNAL_ERROR = 3


class Mumble(Exception):
    """The target answered, but its behavior was incorrect."""


class Offline(Exception):
    """The target could not provide a complete response."""


@dataclass(frozen=True)
class TargetContext:
    target_ip: str
    target_port: int
    round_number: int
    challenge_id: int


@dataclass(frozen=True)
class AdContext(TargetContext):
    # RSCTF_TEAM_ID currently contains the participation ID for A&D.
    participation_id: int
    flag: str


@dataclass(frozen=True)
class KothContext(TargetContext):
    pass


class _TargetValues(TypedDict):
    target_ip: str
    target_port: int
    round_number: int
    challenge_id: int


def _required(name: str) -> str:
    value = os.environ.get(name)
    if value is None or value == "":
        raise KeyError(name)
    return value


def _target_context() -> _TargetValues:
    raw_ip = _required("RSCTF_TARGET_IP")
    try:
        ip = str(ip_address(raw_ip))
    except ValueError as exc:
        raise ValueError(f"RSCTF_TARGET_IP is not an IP address: {raw_ip!r}") from exc

    raw_port = _required("RSCTF_TARGET_PORT")
    try:
        port = int(raw_port)
    except ValueError as exc:
        raise ValueError(f"RSCTF_TARGET_PORT is not an integer: {raw_port!r}") from exc
    if not (1 <= port <= 65535):
        raise ValueError(f"RSCTF_TARGET_PORT is outside 1..65535: {port}")

    raw_round = _required("RSCTF_ROUND_NUMBER")
    try:
        round_number = int(raw_round)
    except ValueError as exc:
        raise ValueError(f"RSCTF_ROUND_NUMBER is not an integer: {raw_round!r}") from exc
    if round_number < 0:
        raise ValueError(f"RSCTF_ROUND_NUMBER is negative: {round_number}")

    raw_challenge = _required("RSCTF_CHALLENGE_ID")
    try:
        challenge_id = int(raw_challenge)
    except ValueError as exc:
        raise ValueError(f"RSCTF_CHALLENGE_ID is not an integer: {raw_challenge!r}") from exc
    if challenge_id <= 0:
        raise ValueError(f"RSCTF_CHALLENGE_ID is not positive: {challenge_id}")

    return {
        "target_ip": ip,
        "target_port": port,
        "round_number": round_number,
        "challenge_id": challenge_id,
    }


def ad_context() -> AdContext:
    values = _target_context()

    raw_part = _required("RSCTF_TEAM_ID")
    try:
        participation_id = int(raw_part)
    except ValueError as exc:
        raise ValueError(f"RSCTF_TEAM_ID is not an integer: {raw_part!r}") from exc
    if participation_id <= 0:
        raise ValueError(f"RSCTF_TEAM_ID is not positive: {participation_id}")

    return AdContext(
        **values,
        participation_id=participation_id,
        flag=_required("RSCTF_FLAG"),
    )


def koth_context() -> KothContext:
    values = _target_context()
    return KothContext(**values)


_CHECKERS: list[Callable[[AdContext], None]] = []


_F = TypeVar("_F", bound=Callable[[AdContext], None])


def ad_checker(fn: _F) -> _F:
    """Register one focused check function with the A&D runner."""
    _CHECKERS.append(fn)
    return fn


checker = ad_checker


def run_ad_checker() -> int:
    """Run registered checkers in shuffled order against one target context."""
    try:
        context = ad_context()
    except (KeyError, ValueError) as exc:
        print(f"internal error: {exc}")
        return int(Verdict.INTERNAL_ERROR)

    order = list(_CHECKERS)
    secrets.SystemRandom().shuffle(order)

    for step in order:
        try:
            step(context)
        except Offline as exc:
            print(f"offline: {exc}")
            return int(Verdict.OFFLINE)
        except Mumble as exc:
            print(f"mumble: {exc}")
            return int(Verdict.MUMBLE)
        except Exception as exc:
            print(f"internal error: {exc}")
            return int(Verdict.INTERNAL_ERROR)

    return int(Verdict.OK)


_KOTH_CHECKERS: list[Callable[[KothContext], None]] = []


_KF = TypeVar("_KF", bound=Callable[[KothContext], None])


def koth_checker(fn: _KF) -> _KF:
    """Register one focused check function with the KotH runner."""
    _KOTH_CHECKERS.append(fn)
    return fn


def run_koth_checker() -> int:
    """Run registered checkers in shuffled order against one target context."""
    try:
        context = koth_context()
    except (KeyError, ValueError) as exc:
        print(f"internal error: {exc}")
        return int(Verdict.INTERNAL_ERROR)

    order = list(_KOTH_CHECKERS)
    secrets.SystemRandom().shuffle(order)

    for step in order:
        try:
            step(context)
        except Offline as exc:
            print(f"offline: {exc}")
            return int(Verdict.OFFLINE)
        except Mumble as exc:
            print(f"mumble: {exc}")
            return int(Verdict.MUMBLE)
        except Exception as exc:
            print(f"internal error: {exc}")
            return int(Verdict.INTERNAL_ERROR)

    return int(Verdict.OK)
